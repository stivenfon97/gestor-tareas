<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EstadoTarea;
use App\Models\Tarea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cambio de estado de una tarea.
 *
 * Exige el motivo que el **destino** requiere, no el que la tarea tenga guardado:
 * `en_espera` pide `motivo_espera` y `cancelada` pide `motivo_cancelacion`, que
 * son campos distintos (D2.9).
 */
class CambiarEstadoTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $destino = $this->destino();

        return [
            'estado' => ['required', Rule::enum(EstadoTarea::class)],
            'motivo_espera' => [
                Rule::requiredIf($destino === EstadoTarea::EnEspera),
                'nullable', 'string', 'max:2000',
            ],
            'motivo_cancelacion' => [
                Rule::requiredIf($destino === EstadoTarea::Cancelada),
                'nullable', 'string', 'max:2000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo_espera.required' => __('tareas.validacion.motivo_espera_obligatorio'),
            'motivo_cancelacion.required' => __('tareas.validacion.motivo_cancelacion_obligatorio'),
        ];
    }

    public function destino(): ?EstadoTarea
    {
        return EstadoTarea::tryFrom((string) $this->input('estado'));
    }

    /**
     * @return array{motivo_espera?: string|null, motivo_cancelacion?: string|null}
     */
    public function motivos(): array
    {
        return [
            'motivo_espera' => $this->input('motivo_espera'),
            'motivo_cancelacion' => $this->input('motivo_cancelacion'),
        ];
    }

    public function tarea(): Tarea
    {
        /** @var Tarea */
        return $this->route('tarea');
    }
}
