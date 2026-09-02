<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TipoDeVista;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de una vista guardada.
 *
 * `usuario_id` **no se acepta**: lo pone el controlador desde la sesión. Si
 * viajara en la petición, guardar una vista a nombre de otro sería cuestión de
 * cambiar un número.
 *
 * `tipo` y `proyecto_id` se fijan al crear y no se editan. Cambiar el tipo
 * dejaría una configuración de tabla —con sus columnas y su tamaño de página—
 * dentro de una vista de tablero, donde ninguno de los dos significa nada; y
 * cambiar el ámbito movería a otro proyecto unos filtros que apuntan a los
 * módulos del primero.
 */
class VistaGuardadaRequest extends FormRequest
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
        $alCrear = $this->isMethod('POST');

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'tipo' => $alCrear
                ? ['required', Rule::in(TipoDeVista::valores())]
                : ['prohibited'],
            'proyecto_id' => $alCrear
                ? ['nullable', 'integer', 'exists:proyectos,id']
                : ['prohibited'],
            'configuracion' => ['present', 'array'],
            'es_predeterminada' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => __('vistas.campos.nombre'),
            'tipo' => __('vistas.campos.tipo'),
            'proyecto_id' => __('vistas.campos.proyecto'),
            'configuracion' => __('vistas.campos.configuracion'),
            'es_predeterminada' => __('vistas.campos.predeterminada'),
        ];
    }
}
