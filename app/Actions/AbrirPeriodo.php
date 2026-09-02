<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EstadoPeriodo;
use App\Exceptions\PeriodoAbiertoExistente;
use App\Models\Periodo;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;

/**
 * Abre un periodo nuevo con su consecutivo, a salvo de creación concurrente.
 *
 * **No usa `max(consecutivo) + 1` sin más.** Dos peticiones que lean el mismo
 * máximo a la vez calcularían el mismo consecutivo y una de las dos chocaría
 * contra el `unique(proyecto_id, consecutivo)` en lugar de recibir el número
 * que le tocaba. Se bloquea la fila del **proyecto** con `lockForUpdate()`
 * dentro de una transacción: la segunda petición espera a que la primera
 * confirme y entonces sí ve el periodo recién creado en su `max()`.
 *
 * No hace falta un contador aparte —como `ContadorTarea` para el código de
 * tarea— porque los periodos nunca se borran (`PeriodoPolicy::delete` siempre
 * niega) y `max()` no tiene huecos que tapar.
 */
final class AbrirPeriodo
{
    /**
     * @param  array{fecha_inicio: string, notas?: string|null}  $datos
     *
     * @throws PeriodoAbiertoExistente
     */
    public function __invoke(Proyecto $proyecto, array $datos): Periodo
    {
        return DB::transaction(function () use ($proyecto, $datos): Periodo {
            $bloqueado = Proyecto::query()->whereKey($proyecto->getKey())->lockForUpdate()->firstOrFail();

            if ($bloqueado->periodoAbierto()->exists()) {
                throw PeriodoAbiertoExistente::paraProyecto($bloqueado);
            }

            $siguiente = (int) ($bloqueado->periodos()->max('consecutivo')) + 1;

            return Periodo::query()->create([
                'proyecto_id' => $bloqueado->getKey(),
                'consecutivo' => $siguiente,
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => null,
                'estado' => EstadoPeriodo::Abierto,
                'notas' => $datos['notas'] ?? null,
            ]);
        });
    }
}
