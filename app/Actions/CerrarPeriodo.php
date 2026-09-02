<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EstadoPeriodo;
use App\Enums\EstadoTarea;
use App\Exceptions\PeriodoYaCerrado;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;

/**
 * Cierra un periodo, traslada lo seleccionado al siguiente y opcionalmente
 * registra el pago. **Todo en una sola transacción**: un cierre a medias
 * —periodo cerrado, tareas sin trasladar— es el peor estado posible del
 * sistema.
 *
 * **Trasladar no es un cambio de estado.** La tarea conserva el suyo: esto
 * actualiza `periodo_id` y `periodo_origen_id` con un `update()` de query
 * builder, a propósito y no con `$tarea->save()` en un bucle. Un `update()`
 * masivo no dispara `TareaObserver`, así que no pasa por
 * `CambiarEstadoTarea`, no revalida la matriz —no hay nada que revalidar: el
 * estado no cambia— y **no escribe en `historial_estados`**. Meter esto por
 * el servicio de transiciones ensuciaría el historial con una transición que
 * nunca ocurrió.
 */
final class CerrarPeriodo
{
    /**
     * @param  array<int, int>  $idsTrasladar  Tareas no terminales del periodo que se cierra, a moverse al siguiente.
     * @param  array{fecha_pago: string, monto_usd: string, tasa_cambio: string, origen_tasa: string, referencia?: string|null, notas?: string|null}|null  $datosPago
     * @return array{cerrado: Periodo, siguiente: Periodo, trasladadas: int}
     *
     * @throws PeriodoYaCerrado
     */
    public function __invoke(
        Periodo $periodo,
        array $idsTrasladar,
        string $fechaInicioSiguiente,
        ?array $datosPago = null,
    ): array {
        return DB::transaction(function () use ($periodo, $idsTrasladar, $fechaInicioSiguiente, $datosPago): array {
            // Se bloquea la fila del proyecto, no la del periodo: es el mismo
            // punto de serialización que usa `AbrirPeriodo`, así que un cierre
            // y una apertura manual no pueden calcular el mismo consecutivo
            // siguiente a la vez.
            /** @var Proyecto $proyecto */
            $proyecto = Proyecto::query()->whereKey($periodo->proyecto_id)->lockForUpdate()->firstOrFail();

            /** @var Periodo $bloqueado */
            $bloqueado = Periodo::query()->whereKey($periodo->getKey())->lockForUpdate()->firstOrFail();

            if ($bloqueado->estado !== EstadoPeriodo::Abierto) {
                throw PeriodoYaCerrado::alCerrar($bloqueado);
            }

            $bloqueado->update([
                'estado' => EstadoPeriodo::Cerrado,
                // La fecha real de cierre es hoy, no la prevista que pudiera
                // tener guardada: sobrepasar la fecha prevista es válido y
                // frecuente, y el cierre siempre la sustituye por la de verdad.
                'fecha_fin' => now()->toDateString(),
            ]);

            $siguienteConsecutivo = (int) ($proyecto->periodos()->max('consecutivo')) + 1;

            $siguiente = Periodo::query()->create([
                'proyecto_id' => $proyecto->getKey(),
                'consecutivo' => $siguienteConsecutivo,
                'fecha_inicio' => $fechaInicioSiguiente,
                'fecha_fin' => null,
                'estado' => EstadoPeriodo::Abierto,
            ]);

            $trasladadas = 0;

            if ($idsTrasladar !== []) {
                $trasladadas = $bloqueado->tareas()
                    ->whereIn('id', $idsTrasladar)
                    // Defensa en profundidad: aunque la request ya solo ofrece
                    // las no terminales, una terminal nunca se traslada aquí
                    // tampoco si alguien fuerza el id a mano.
                    ->whereNotIn('estado', [EstadoTarea::Entregada, EstadoTarea::Cancelada])
                    // `orden` no se toca (D4.10, decisión 0.4 de la fase 09).
                    // Sigue siendo del proyecto, no del periodo: cada vista
                    // que lo usa (columna del tablero, grupo de la lista)
                    // ordena dentro de su propio `WHERE`, así que un valor
                    // compartido entre periodos nunca colisiona con el de
                    // otro grupo, solo dice "más arriba o más abajo que sus
                    // vecinos en esta consulta". Renumerar al trasladar
                    // obligaría a decidir dónde entra cada tarea arrastrada
                    // dentro del periodo nuevo -¿al principio? ¿al final?- y
                    // esa pregunta no tiene una respuesta mejor que dejar que
                    // la reordenación manual de la fase 08 la resuelva si
                    // hace falta.
                    ->update([
                        'periodo_id' => $siguiente->getKey(),
                        'periodo_origen_id' => $bloqueado->getKey(),
                    ]);
            }

            if ($datosPago !== null) {
                Pago::query()->create($datosPago + ['periodo_id' => $bloqueado->getKey()]);
            }

            return [
                'cerrado' => $bloqueado->refresh(),
                'siguiente' => $siguiente,
                'trasladadas' => $trasladadas,
            ];
        });
    }
}
