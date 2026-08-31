<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EstadoTarea;
use App\Enums\TipoTarea;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Crea de golpe las tareas confirmadas en la previsualización de captura rápida.
 *
 * **Todo o nada.** Diez líneas pegadas de un correo son una sola intención; que
 * entren seis y fallen cuatro deja al usuario sin saber cuáles repetir, y
 * repetir el pegado completo duplicaría las seis que sí entraron.
 */
final class CrearTareasEnLote
{
    /**
     * @param  array<int, array{
     *     nombre: string,
     *     estado?: string|null,
     *     descripcion?: string|null,
     *     tipo?: string|null,
     *     prioridad?: string|null,
     *     modulo_id?: int|null,
     *     modulo_nuevo?: string|null,
     *     motivo_espera?: string|null,
     *     motivo_cancelacion?: string|null
     * }>  $filas
     * @return array<int, Tarea>
     */
    public function __invoke(Proyecto $proyecto, array $filas, User $autor): array
    {
        return DB::transaction(function () use ($proyecto, $filas, $autor): array {
            // Los módulos nuevos se resuelven primero y se cachean por nombre:
            // dos líneas que mencionan el mismo módulo inexistente deben crear
            // uno, no dos.
            $creados = [];
            $tareas = [];

            // Todas las tareas del lote caen en el periodo abierto. Si no hay
            // ninguno, nacen sin periodo y la interfaz ya lo advirtió.
            $periodoId = $proyecto->periodoAbierto()->value('id');

            foreach ($filas as $fila) {
                $moduloId = $fila['modulo_id'] ?? null;
                $nombreNuevo = trim((string) ($fila['modulo_nuevo'] ?? ''));

                if ($moduloId === null && $nombreNuevo !== '') {
                    $moduloId = $creados[$nombreNuevo] ??= $this->crearModulo($proyecto, $nombreNuevo);
                }

                $tipo = TipoTarea::tryFrom((string) ($fila['tipo'] ?? '')) ?? TipoTarea::Desarrollo;

                $tareas[] = Tarea::query()->create([
                    'proyecto_id' => $proyecto->getKey(),
                    'modulo_id' => $moduloId,
                    'periodo_id' => $periodoId,
                    'nombre' => $fila['nombre'],
                    'descripcion' => $fila['descripcion'] ?? null,
                    'estado' => EstadoTarea::tryFrom((string) ($fila['estado'] ?? '')) ?? EstadoTarea::Pendiente,
                    'tipo' => $tipo,
                    // Null deja que el observer precargue según el tipo.
                    'prioridad' => $fila['prioridad'] ?? null,
                    'motivo_espera' => $fila['motivo_espera'] ?? null,
                    'motivo_cancelacion' => $fila['motivo_cancelacion'] ?? null,
                    'creado_por' => $autor->getKey(),
                ]);
            }

            return $tareas;
        });
    }

    /**
     * Módulo raíz al final de sus hermanos, igual que la creación manual del
     * árbol de la fase 05.
     */
    private function crearModulo(Proyecto $proyecto, string $nombre): int
    {
        return $proyecto->modulos()->create([
            'nombre' => $nombre,
            'parent_id' => null,
            'orden' => (int) $proyecto->modulos()->whereNull('parent_id')->max('orden') + 1,
        ])->getKey();
    }
}
