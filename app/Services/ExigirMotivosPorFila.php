<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoTarea;
use Illuminate\Validation\ValidationException;

/**
 * `en_espera` y `cancelada` exigen su motivo también al crear (D2.9), y el
 * observer aborta el guardado sin él. Comprobarlo aquí, antes de la creación
 * en lote, evita que la mitad de las filas se cree y la otra mitad reviente a
 * medio camino: `CrearTareasEnLote` es todo o nada, pero el error de un
 * motivo faltante debe llegar antes de abrir la transacción.
 *
 * Usado por `CapturaRapidaController` y `ReunionTareaController`: son el
 * mismo flujo de captura rápida, uno desde el proyecto y otro desde una
 * reunión (fase 10).
 */
final class ExigirMotivosPorFila
{
    /**
     * @param  array<int, array<string, mixed>>  $filas
     *
     * @throws ValidationException
     */
    public function __invoke(array $filas): void
    {
        $errores = [];

        foreach ($filas as $indice => $fila) {
            $campo = EstadoTarea::from((string) $fila['estado'])->motivoRequerido();

            if ($campo !== null && blank($fila[$campo] ?? null)) {
                $errores["filas.{$indice}.{$campo}"] = __("tareas.validacion.{$campo}_obligatorio");
            }
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }
}
