<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Exceptions\TransicionNoPermitida;
use App\Models\Tarea;
use Illuminate\Support\Facades\DB;

/**
 * Puerta única del cambio de estado de una tarea.
 *
 * **Ninguna vía de escritura toca `estado` directamente sobre el modelo.** Un
 * `update(['estado' => …])` suelto en un controlador se salta las fechas
 * automáticas, y en la fase 08 el Kanban movería tarjetas sin que
 * `fecha_inicio` llegara a rellenarse nunca.
 *
 * Reparto de responsabilidades, que no es duplicación sino profundidad:
 *
 * - **Este servicio** valida antes de tocar la base, rellena las fechas y
 *   devuelve el resultado que la interfaz necesita.
 * - **`TareaObserver::updating`** vuelve a validar y exige el motivo: es la
 *   última red, la que cubre a un seeder o un comando de consola que no pase
 *   por aquí.
 * - **`TareaObserver::updated`** escribe el historial. Nunca este servicio: si
 *   el historial dependiera de que alguien llame al servicio, un día habría un
 *   camino que no lo llama.
 */
final class CambiarEstadoTarea
{
    /**
     * @param  array{motivo_espera?: string|null, motivo_cancelacion?: string|null}  $motivos
     * @return array{estado: string, transiciones: array<int, array{valor: string, etiqueta: string, requiere_motivo: string|null}>, fecha_inicio: string|null, fecha_fin: string|null}
     *
     * @throws TransicionNoPermitida
     */
    public function __invoke(Tarea $tarea, EstadoTarea $destino, array $motivos = []): array
    {
        $origen = $tarea->estado;

        if ($origen === $destino) {
            // Un estado hacia sí mismo no es una transición y no se registra.
            return self::resultado($tarea);
        }

        if (! $origen->puedeTransicionarA($destino)) {
            throw TransicionNoPermitida::entre($origen, $destino);
        }

        DB::transaction(function () use ($tarea, $destino, $motivos): void {
            $tarea->estado = $destino;

            // Solo se escribe el motivo que este destino exige. Aceptar el otro
            // dejaría un `motivo_cancelacion` en una tarea que nadie canceló.
            $campo = $destino->motivoRequerido();

            if ($campo !== null) {
                $tarea->{$campo} = $motivos[$campo] ?? null;
            }

            $this->ajustarFechas($tarea, $destino);

            // El observer valida de nuevo, exige el motivo y escribe el
            // historial en `updated`.
            $tarea->save();
        });

        return self::resultado($tarea->refresh());
    }

    /**
     * Transiciones que la interfaz puede ofrecer desde el estado actual.
     *
     * **La interfaz nunca deduce la matriz.** Replicarla en el cliente crearía
     * una cuarta fuente de verdad —enum, skill, diagrama y ahora Vue— y sería la
     * que nadie comprueba.
     *
     * @return array<int, array{valor: string, etiqueta: string, requiere_motivo: string|null}>
     */
    public static function disponiblesPara(EstadoTarea $estado): array
    {
        return array_map(static fn (EstadoTarea $destino): array => [
            'valor' => $destino->value,
            'etiqueta' => $destino->etiqueta(),
            'requiere_motivo' => $destino->motivoRequerido(),
        ], $estado->transicionesPermitidas());
    }

    /**
     * `fecha_inicio` marca **cuándo se empezó de verdad**, así que se rellena la
     * primera vez que la tarea entra en `en_proceso` y no se sobrescribe: volver
     * de `en_espera` o de una revisión fallida no es empezar de nuevo.
     *
     * `fecha_fin` marca **cuándo quedó lista**, y sí se actualiza al reentrar en
     * `terminada`: si la revisión falló y hubo que retocar, la fecha buena es la
     * del segundo intento, que es la que el cliente ve en el reporte.
     */
    private function ajustarFechas(Tarea $tarea, EstadoTarea $destino): void
    {
        if ($destino === EstadoTarea::EnProceso && $tarea->fecha_inicio === null) {
            $tarea->fecha_inicio = now();
        }

        if ($destino === EstadoTarea::Terminada) {
            $tarea->fecha_fin = now();
        }
    }

    /**
     * @return array{estado: string, transiciones: array<int, array{valor: string, etiqueta: string, requiere_motivo: string|null}>, fecha_inicio: string|null, fecha_fin: string|null}
     */
    private static function resultado(Tarea $tarea): array
    {
        return [
            'estado' => $tarea->estado->value,
            'transiciones' => self::disponiblesPara($tarea->estado),
            'fecha_inicio' => $tarea->fecha_inicio?->toDateString(),
            'fecha_fin' => $tarea->fecha_fin?->toDateString(),
        ];
    }
}
