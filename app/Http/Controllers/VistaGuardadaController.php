<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\VistaGuardadaRequest;
use App\Models\VistaGuardada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Vistas guardadas: crear, renombrar, duplicar y borrar.
 *
 * **La propiedad no se resuelve con la policy, se resuelve con la consulta.**
 * `Gate::before` deja pasar al administrador por encima de todas las policies,
 * y para los datos de un proyecto eso está bien: un administrador tiene que
 * poder entrar a arreglar lo que sea. Una vista guardada no es un dato del
 * proyecto, es la preferencia de una persona, y no hay ningún permiso que deba
 * conceder renombrar la de otro. Por eso cada método busca la fila **dentro de
 * las del usuario de la sesión** y responde 404 si no está: un recurso privado
 * no confirma que exista.
 */
class VistaGuardadaController extends Controller
{
    public function store(VistaGuardadaRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $vista = DB::transaction(function () use ($request, $datos): VistaGuardada {
            $this->desmarcarOtraPredeterminada(
                $request,
                $datos['tipo'],
                marcar: (bool) ($datos['es_predeterminada'] ?? false),
            );

            return VistaGuardada::query()->create($datos + [
                'usuario_id' => $request->user()->getKey(),
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('vistas.guardada', ['nombre' => $vista->nombre]),
        ]);

        return back();
    }

    public function update(VistaGuardadaRequest $request, VistaGuardada $vista): RedirectResponse
    {
        $suya = $this->suya($request, $vista);
        $datos = $request->validated();

        DB::transaction(function () use ($request, $suya, $datos): void {
            $this->desmarcarOtraPredeterminada(
                $request,
                $suya->tipo->value,
                marcar: (bool) ($datos['es_predeterminada'] ?? false),
                salvo: $suya->getKey(),
            );

            $suya->update($datos);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('vistas.actualizada', ['nombre' => $suya->nombre]),
        ]);

        return back();
    }

    /**
     * Duplicar nace **sin** la marca de predeterminada.
     *
     * Copiar una vista es querer una variante, no reemplazar la de referencia:
     * heredar la marca cambiaría en silencio lo que se abre por defecto.
     */
    public function duplicar(Request $request, VistaGuardada $vista): RedirectResponse
    {
        $suya = $this->suya($request, $vista);

        $copia = $suya->replicate(['es_predeterminada', 'marca_predeterminada']);
        $copia->nombre = __('vistas.copia_de', ['nombre' => $suya->nombre]);
        $copia->es_predeterminada = false;
        $copia->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('vistas.duplicada', ['nombre' => $copia->nombre]),
        ]);

        return back();
    }

    public function destroy(Request $request, VistaGuardada $vista): RedirectResponse
    {
        $suya = $this->suya($request, $vista);
        $nombre = $suya->nombre;

        $suya->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('vistas.borrada', ['nombre' => $nombre]),
        ]);

        return back();
    }

    /**
     * La vista, buscada **entre las del usuario de la sesión**.
     *
     * 404 y no 403: confirmar que la vista existe ya diría que otra persona
     * tiene una vista con ese id, y eso es más de lo que hace falta contestar.
     */
    private function suya(Request $request, VistaGuardada $vista): VistaGuardada
    {
        $suya = VistaGuardada::query()
            ->whereKey($vista->getKey())
            ->where('usuario_id', $request->user()->getKey())
            ->first();

        abort_if($suya === null, 404);

        return $suya;
    }

    /**
     * Deja libre el hueco de «predeterminada» antes de ocuparlo.
     *
     * La cerradura de verdad es el índice único de `vistas_guardadas`, que
     * garantiza una sola por usuario y tipo pase lo que pase. Esto es la
     * cortesía que hace que marcar una vista **mueva** la marca en vez de dar
     * un error de base de datos: sin ello, el usuario tendría que desmarcar la
     * anterior a mano para poder marcar la nueva.
     */
    private function desmarcarOtraPredeterminada(
        Request $request,
        string $tipo,
        bool $marcar,
        ?int $salvo = null,
    ): void {
        if (! $marcar) {
            return;
        }

        VistaGuardada::query()
            ->where('usuario_id', $request->user()->getKey())
            ->where('tipo', $tipo)
            ->where('es_predeterminada', true)
            ->when($salvo !== null, fn ($consulta) => $consulta->whereKeyNot($salvo))
            ->update(['es_predeterminada' => false]);
    }
}
