<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EstadoProyecto;
use App\Models\Proyecto;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de proyectos.
 *
 * Dos reglas que no son de formato sino de dominio:
 *
 * - El cliente debe estar **activo**. Un cliente desactivado sigue teniendo sus
 *   proyectos, pero no admite proyectos nuevos.
 * - `prefijo_tarea` es **inmutable** en cuanto el proyecto emitió su primer
 *   código. Aquí se rechaza con un 422 legible; `ProyectoObserver` lo vuelve a
 *   comprobar para que ninguna otra vía de escritura pueda saltárselo.
 */
class ProyectoRequest extends FormRequest
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
        $proyecto = $this->route('proyecto');

        return [
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where('activo', true),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('proyectos', 'slug')->ignore($proyecto),
            ],
            'prefijo_tarea' => [
                'required',
                'string',
                'min:2',
                'max:10',
                // Solo letras y números: el prefijo se concatena al correlativo
                // (`MAQ-0001`) y un guion o un espacio harían el código
                // ambiguo al partirlo.
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('proyectos', 'prefijo_tarea')->ignore($proyecto),
                $this->prefijoInmutable($proyecto),
            ],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'estado' => ['required', Rule::enum(EstadoProyecto::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cliente_id.exists' => __('proyectos.validacion.cliente_inactivo'),
            'slug.regex' => __('proyectos.validacion.slug_formato'),
            'prefijo_tarea.regex' => __('proyectos.validacion.prefijo_formato'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('prefijo_tarea'))) {
            $this->merge(['prefijo_tarea' => Proyecto::normalizarPrefijo($this->string('prefijo_tarea')->value())]);
        }
    }

    /**
     * Rechaza el cambio de prefijo cuando el proyecto ya emitió códigos.
     * Reenviar el mismo valor no es un cambio y pasa sin ruido.
     */
    private function prefijoInmutable(mixed $proyecto): Closure
    {
        return function (string $atributo, mixed $valor, Closure $falla) use ($proyecto): void {
            if (! $proyecto instanceof Proyecto) {
                return;
            }

            if ($valor === $proyecto->prefijo_tarea || ! $proyecto->tieneCodigosEmitidos()) {
                return;
            }

            $falla(__('proyectos.validacion.prefijo_inmutable'));
        };
    }
}
