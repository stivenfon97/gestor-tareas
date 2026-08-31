<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Modulo;
use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y renombrado de un módulo.
 *
 * `parent_id` solo se acepta si es un módulo **del mismo proyecto**: sin esa
 * comprobación se podría colgar una rama del árbol de otro cliente enviando un
 * id a mano.
 */
class ModuloRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('modulos', 'id')->where('proyecto_id', $this->proyectoId()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.exists' => __('modulos.validacion.padre_de_otro_proyecto'),
        ];
    }

    private function proyectoId(): ?int
    {
        $proyecto = $this->route('proyecto');

        if ($proyecto instanceof Proyecto) {
            return $proyecto->getKey();
        }

        $modulo = $this->route('modulo');

        return $modulo instanceof Modulo ? $modulo->proyecto_id : null;
    }
}
