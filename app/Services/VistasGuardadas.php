<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TipoDeVista;
use App\Models\Modulo;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\User;
use App\Models\VistaGuardada;
use Illuminate\Http\Request;

/**
 * Aplicar y ofrecer vistas guardadas.
 *
 * Como el estado de toda vista vive en la URL desde la fase 07, una vista
 * guardada es **un nombre y una cadena de consulta**. Aplicarla es navegar; no
 * hace falta ningún mecanismo paralelo.
 *
 * Lo que sí hace falta es tolerar referencias muertas. Una vista guardada en
 * julio filtra por un módulo que en noviembre puede no existir, y hay dos
 * formas malas de responder a eso: reventar —la vista deja de abrirse y el
 * usuario no sabe por qué— o filtrar por un id inexistente y devolver cero
 * filas fingiendo que no hay trabajo. Aquí se hace la tercera: se ignora lo que
 * ya no existe, se aplica el resto y **se avisa una vez** de que la vista se
 * aplicó parcialmente.
 */
final class VistasGuardadas
{
    /**
     * Las vistas de este usuario aplicables a esta pantalla, ya saneadas.
     *
     * @return array<int, array{id: int, nombre: string, tipo: string, es_predeterminada: bool, ambito: string, configuracion: array<string, mixed>, parcial: bool}>
     */
    public static function para(User $usuario, TipoDeVista $tipo, ?Proyecto $proyecto): array
    {
        return VistaGuardada::query()
            ->where('usuario_id', $usuario->getKey())
            ->where('tipo', $tipo->value)
            ->delAlcance($proyecto?->getKey())
            ->orderByDesc('es_predeterminada')
            ->orderBy('nombre')
            ->get()
            ->map(static function (VistaGuardada $vista) use ($proyecto): array {
                $saneada = self::sanear($vista->configuracion, $proyecto);

                return [
                    'id' => $vista->id,
                    'nombre' => $vista->nombre,
                    'tipo' => $vista->tipo->value,
                    'es_predeterminada' => $vista->es_predeterminada,
                    // Global o de este proyecto: la diferencia importa al
                    // duplicar y al borrar, y se ve de un vistazo en la lista.
                    'ambito' => $vista->proyecto_id === null ? 'global' : 'proyecto',
                    'configuracion' => $saneada['configuracion'],
                    'parcial' => $saneada['parcial'],
                ];
            })
            ->all();
    }

    /**
     * La configuración de la predeterminada, si hay una y la petición no trae
     * ya parámetros propios.
     *
     * **Solo se aplica en una URL desnuda.** Si el usuario llegó con filtros —de
     * un enlace compartido, del botón «atrás», de otra vista— la predeterminada
     * no debe pisárselos: aplicar algo que nadie pidió encima de algo que sí se
     * pidió es la forma más rápida de que una vista guardada se perciba como un
     * fallo.
     *
     * @return array<string, mixed>|null
     */
    public static function predeterminada(
        Request $request,
        TipoDeVista $tipo,
        ?Proyecto $proyecto,
    ): ?array {
        if ($request->query() !== []) {
            return null;
        }

        $vista = VistaGuardada::query()
            ->where('usuario_id', $request->user()->getKey())
            ->where('tipo', $tipo->value)
            ->where('es_predeterminada', true)
            ->delAlcance($proyecto?->getKey())
            // Una de proyecto gana a una global: es la más específica para la
            // pantalla que se está abriendo.
            ->orderByRaw('proyecto_id is null')
            ->first();

        if ($vista === null) {
            return null;
        }

        return self::sanear($vista->configuracion, $proyecto)['configuracion'];
    }

    /**
     * Quita de una configuración lo que ya no existe.
     *
     * Solo se revisan los filtros que apuntan a **filas de otras tablas**: un
     * `filter[estado]=en_espera` no puede quedarse huérfano porque los estados
     * son un enum, y un `sort` o unas `columnas` que ya no existen los ignora
     * quien los recibe sin dejar la vista vacía.
     *
     * @param  array<string, mixed>  $configuracion
     * @return array{configuracion: array<string, mixed>, parcial: bool}
     */
    public static function sanear(array $configuracion, ?Proyecto $proyecto): array
    {
        $filtros = $configuracion['filter'] ?? [];

        if (! is_array($filtros) || $filtros === []) {
            return ['configuracion' => $configuracion, 'parcial' => false];
        }

        $vivos = $filtros;

        if (isset($vivos['modulo_id']) && ! self::moduloVive((string) $vivos['modulo_id'], $proyecto)) {
            unset($vivos['modulo_id']);
        }

        if (isset($vivos['periodo_id']) && ! self::periodoVive((string) $vivos['periodo_id'], $proyecto)) {
            unset($vivos['periodo_id']);
        }

        if (isset($vivos['proyecto_id']) && ! Proyecto::query()->whereKey($vivos['proyecto_id'])->exists()) {
            unset($vivos['proyecto_id']);
        }

        $parcial = $vivos !== $filtros;

        if ($vivos === []) {
            unset($configuracion['filter']);
        } else {
            $configuracion['filter'] = $vivos;
        }

        return ['configuracion' => $configuracion, 'parcial' => $parcial];
    }

    /**
     * Un módulo vive si existe **y es del proyecto que se está mirando**.
     *
     * Sin la segunda mitad, una vista global guardada dentro del proyecto A
     * seguiría filtrando por su módulo al abrirla en el proyecto B, y el
     * resultado serían cero filas sin ninguna explicación.
     */
    private static function moduloVive(string $id, ?Proyecto $proyecto): bool
    {
        return Modulo::query()
            ->whereKey($id)
            ->when($proyecto !== null, fn ($consulta) => $consulta->where('proyecto_id', $proyecto->getKey()))
            ->exists();
    }

    private static function periodoVive(string $id, ?Proyecto $proyecto): bool
    {
        return Periodo::query()
            ->whereKey($id)
            ->when($proyecto !== null, fn ($consulta) => $consulta->where('proyecto_id', $proyecto->getKey()))
            ->exists();
    }
}
