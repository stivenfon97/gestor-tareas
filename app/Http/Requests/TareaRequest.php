<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EstadoPeriodo;
use App\Enums\PrioridadTarea;
use App\Enums\TipoTarea;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de una tarea.
 *
 * **`estado` no aparece aquí.** Tiene su propio endpoint
 * (`EstadoTareaController`) porque mezclarlo con el formulario general
 * permitiría saltarse la matriz de transiciones por descuido: bastaría con
 * enviar el campo entre los demás.
 *
 * `tipo` tampoco se acepta al editar: se fija al crear y no cambia (D1.3).
 */
class TareaRequest extends FormRequest
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
        $proyecto = $this->proyecto();
        $tarea = $this->route('tarea');

        $reglas = [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:20000'],
            'prioridad' => ['required', Rule::enum(PrioridadTarea::class)],
            'modulo_id' => [
                'nullable',
                'integer',
                Rule::exists('modulos', 'id')->where('proyecto_id', $proyecto->getKey()),
            ],
            'periodo_id' => [
                'nullable',
                'integer',
                Rule::exists('periodos', 'id')->where('proyecto_id', $proyecto->getKey()),
                $this->periodoAbiertoOSinCambios($tarea),
            ],
            'tarea_origen_id' => [
                'nullable',
                'integer',
                Rule::exists('tareas', 'id')->where('proyecto_id', $proyecto->getKey()),
                $this->origenCoherente($tarea),
            ],
        ];

        if (! $tarea instanceof Tarea) {
            // El tipo se fija al crear y no cambia después.
            $reglas['tipo'] = ['required', Rule::enum(TipoTarea::class)];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'modulo_id.exists' => __('tareas.validacion.modulo_de_otro_proyecto'),
            'periodo_id.exists' => __('tareas.validacion.periodo_de_otro_proyecto'),
            'tarea_origen_id.exists' => __('tareas.validacion.origen_de_otro_proyecto'),
        ];
    }

    /**
     * Un periodo cerrado no admite tareas nuevas ni asignaciones nuevas (D4.9).
     *
     * Deja pasar el `periodo_id` que la tarea ya tenía sin tocar: el
     * desplegable de edición lista todos los periodos del proyecto, incluido
     * uno que se cerró después de que esta tarea naciera ahí, y guardar el
     * resto del formulario sin cambiar el periodo no puede fallar por eso.
     */
    private function periodoAbiertoOSinCambios(?Tarea $tarea): Closure
    {
        return function (string $atributo, mixed $valor, Closure $falla) use ($tarea): void {
            if (blank($valor)) {
                return;
            }

            if ($tarea instanceof Tarea && (int) $valor === $tarea->periodo_id) {
                return;
            }

            $periodo = Periodo::query()->find($valor);

            if ($periodo instanceof Periodo && $periodo->estado !== EstadoPeriodo::Abierto) {
                $falla(__('tareas.validacion.periodo_cerrado'));
            }
        };
    }

    /**
     * El proyecto sale de la ruta al crear y de la propia tarea al editar.
     */
    public function proyecto(): Proyecto
    {
        $proyecto = $this->route('proyecto');

        if ($proyecto instanceof Proyecto) {
            return $proyecto;
        }

        /** @var Tarea $tarea */
        $tarea = $this->route('tarea');

        return $tarea->proyecto;
    }

    /**
     * `tarea_origen_id` solo tiene sentido en `correccion` y `soporte`, y nunca
     * apunta a la propia tarea. El resto —cadenas circulares— lo cierra
     * `TareaObserver`, que es la cerradura de verdad.
     */
    private function origenCoherente(mixed $tarea): Closure
    {
        return function (string $atributo, mixed $valor, Closure $falla) use ($tarea): void {
            if (blank($valor)) {
                return;
            }

            $tipo = $tarea instanceof Tarea
                ? $tarea->tipo
                : TipoTarea::tryFrom((string) $this->input('tipo'));

            if (! in_array($tipo, [TipoTarea::Correccion, TipoTarea::Soporte], strict: true)) {
                $falla(__('tareas.validacion.origen_solo_correccion_o_soporte'));

                return;
            }

            if ($tarea instanceof Tarea && (int) $valor === $tarea->getKey()) {
                $falla(__('tareas.validacion.origen_a_si_misma'));
            }
        };
    }
}
