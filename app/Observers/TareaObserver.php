<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\EstadoTarea;
use App\Enums\TipoTarea;
use App\Exceptions\TransicionNoPermitida;
use App\Exceptions\VinculoDeOrigenInvalido;
use App\Models\ContadorTarea;
use App\Models\HistorialEstado;
use App\Models\Proyecto;
use App\Models\Tarea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TareaObserver
{
    /**
     * Precarga la prioridad, exige el motivo del estado inicial y asigna el
     * código correlativo.
     */
    public function creating(Tarea $tarea): void
    {
        if ($tarea->prioridad === null && $tarea->tipo !== null) {
            // Precarga, no restricción: sigue siendo editable después.
            $tarea->prioridad = $tarea->tipo->prioridadPorDefecto();
        }

        $tarea->estado ??= EstadoTarea::Pendiente;

        $this->exigirMotivo($tarea->estado, $tarea);

        $this->validarOrigen($tarea);

        if (blank($tarea->codigo)) {
            $tarea->codigo = $this->siguienteCodigo($tarea->proyecto_id);
        }
    }

    /**
     * Guarda el cambio de estado: valida contra la matriz, exige el motivo del
     * destino y limpia el del estado que se abandona.
     */
    public function updating(Tarea $tarea): void
    {
        if ($tarea->isDirty(['tarea_origen_id', 'tipo', 'proyecto_id'])) {
            $this->validarOrigen($tarea);
        }

        if (! $tarea->isDirty('estado')) {
            return;
        }

        /** @var EstadoTarea $anterior */
        $anterior = $tarea->getOriginal('estado');
        $nuevo = $tarea->estado;

        if (! $anterior->puedeTransicionarA($nuevo)) {
            throw TransicionNoPermitida::entre($anterior, $nuevo);
        }

        $this->exigirMotivo($nuevo, $tarea);

        // El motivo del estado que se abandona deja de aplicar. El texto no se
        // pierde: queda en el historial.
        if ($anterior === EstadoTarea::EnEspera) {
            $tarea->motivo_espera = null;
        }

        if ($anterior === EstadoTarea::Cancelada) {
            $tarea->motivo_cancelacion = null;
        }
    }

    /**
     * Escribe el historial una vez que el cambio está realmente persistido.
     *
     * Se hace en `updated` y no en `updating` porque un UPDATE puede fallar
     * después del evento previo (interbloqueo, restricción) y dejaría un
     * registro histórico de un cambio que nunca ocurrió.
     *
     * **Nunca se escribe el historial desde un controlador**: si depende de que
     * alguien se acuerde, algún día no se acuerda.
     */
    public function updated(Tarea $tarea): void
    {
        if (! $tarea->wasChanged('estado')) {
            return;
        }

        HistorialEstado::query()->create([
            'tarea_id' => $tarea->id,
            'usuario_id' => Auth::id(),
            'estado_anterior' => $tarea->getOriginal('estado'),
            'estado_nuevo' => $tarea->estado,
            'comentario' => $tarea->estado->motivoRequerido() !== null
                ? $tarea->{$tarea->estado->motivoRequerido()}
                : null,
        ]);
    }

    /**
     * `tarea_origen_id` solo apunta a una tarea del mismo proyecto, desde una
     * tarea de tipo `correccion` o `soporte`, y sin cerrar una cadena circular.
     *
     * Va en el observer y no solo en el `FormRequest` porque el vínculo es lo
     * que hace auditable la regla D2.6: si un seeder o un comando pueden
     * torcerlo, la respuesta a "cuántas correcciones generó esta entrega" deja
     * de ser fiable sin que nadie se entere.
     */
    protected function validarOrigen(Tarea $tarea): void
    {
        if ($tarea->tarea_origen_id === null) {
            return;
        }

        if (! in_array($tarea->tipo, [TipoTarea::Correccion, TipoTarea::Soporte], strict: true)) {
            throw VinculoDeOrigenInvalido::tipoNoAdmiteOrigen($tarea->tipo);
        }

        if ($tarea->exists && $tarea->tarea_origen_id === $tarea->getKey()) {
            throw VinculoDeOrigenInvalido::aSiMisma();
        }

        $origen = Tarea::withTrashed()->find($tarea->tarea_origen_id);

        if ($origen === null || $origen->proyecto_id !== $tarea->proyecto_id) {
            throw VinculoDeOrigenInvalido::deOtroProyecto();
        }

        // Se sube por la cadena de orígenes: A→B→C→A no lo detecta mirar solo
        // un eslabón, y una cadena cerrada cuelga cualquier recorrido posterior.
        $visitados = $tarea->exists ? [$tarea->getKey() => true] : [];
        $actual = $origen;

        while ($actual !== null) {
            if (isset($visitados[$actual->getKey()])) {
                throw VinculoDeOrigenInvalido::cadenaCircular();
            }

            $visitados[$actual->getKey()] = true;
            $actual = $actual->tarea_origen_id === null
                ? null
                : Tarea::withTrashed()->find($actual->tarea_origen_id);
        }
    }

    /**
     * Siguiente código `{PREFIJO}-{NNNN}` del proyecto.
     *
     * El correlativo es por proyecto y continuo de por vida: no se reinicia por
     * periodo. Se toma de `contadores_tarea` con `lockForUpdate()`, no de
     * `max(codigo) + 1`: dos creaciones simultáneas leerían el mismo máximo y
     * devolverían el mismo número, y un borrado forzado haría que un código se
     * reutilizara. El `unique` de `codigo` es la última red, no la primera.
     */
    protected function siguienteCodigo(int $proyectoId): string
    {
        return DB::transaction(function () use ($proyectoId): string {
            $contador = ContadorTarea::query()
                ->where('proyecto_id', $proyectoId)
                ->lockForUpdate()
                ->first();

            if ($contador === null) {
                // Red de seguridad para proyectos creados sin pasar por el
                // observer (por ejemplo, insertados a mano en una migración).
                ContadorTarea::query()->firstOrCreate(
                    ['proyecto_id' => $proyectoId],
                    ['ultimo_numero' => 0],
                );

                $contador = ContadorTarea::query()
                    ->where('proyecto_id', $proyectoId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $siguiente = $contador->ultimo_numero + 1;
            $contador->update(['ultimo_numero' => $siguiente]);

            $prefijo = Proyecto::withTrashed()->findOrFail($proyectoId)->prefijo_tarea;

            return sprintf('%s-%04d', $prefijo, $siguiente);
        });
    }

    /**
     * `en_espera` exige `motivo_espera` y `cancelada` exige
     * `motivo_cancelacion`. Son campos distintos: mezclarlos hace ilegible el
     * historial.
     */
    protected function exigirMotivo(EstadoTarea $destino, Tarea $tarea): void
    {
        $campo = $destino->motivoRequerido();

        if ($campo !== null && blank($tarea->{$campo})) {
            throw TransicionNoPermitida::faltaMotivo($destino, $campo);
        }
    }
}
