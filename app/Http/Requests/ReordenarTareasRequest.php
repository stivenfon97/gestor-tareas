<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * El orden completo de una columna del tablero.
 *
 * Llega el conjunto entero y no la tarjeta movida: un arrastre cambia la
 * posición de varias tarjetas a la vez, y una petición por tarjeta deja la
 * columna a medias en cuanto una falla.
 *
 * Todos los ids tienen que ser del proyecto de la ruta. Sin esa comprobación,
 * un id ajeno enviado a mano reordenaría tareas de otro cliente.
 */
class ReordenarTareasRequest extends FormRequest
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
        return [
            'tareas' => ['present', 'array'],
            'tareas.*' => [
                'integer',
                'distinct',
                Rule::exists('tareas', 'id')->where('proyecto_id', $this->proyecto()->getKey()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tareas.*.exists' => __('tareas.validacion.tarea_de_otro_proyecto'),
        ];
    }

    /** @return array<int, int> */
    public function tareas(): array
    {
        return array_map(intval(...), array_values($this->validated('tareas')));
    }

    private function proyecto(): Proyecto
    {
        /** @var Proyecto */
        return $this->route('proyecto');
    }
}
