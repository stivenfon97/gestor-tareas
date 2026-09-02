<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EstadoPeriodo;
use App\Models\Periodo;
use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Abrir un periodo y editar uno existente.
 *
 * **`estado` no aparece aquí.** La única transición —`abierto` → `cerrado`— la
 * hace `CierrePeriodoController`, con su propia request: es una operación en
 * lote con traslado y pago detrás, no un campo que se cambie sueltro en un
 * formulario general.
 */
class PeriodoRequest extends FormRequest
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
        $periodo = $this->periodo();

        if ($periodo === null) {
            return [
                'fecha_inicio' => ['required', 'date'],
                'notas' => ['nullable', 'string', 'max:2000'],
            ];
        }

        // `fecha_inicio` y `fecha_fin` solo son editables mientras el periodo
        // sigue abierto. `fecha_fin` aquí es la fecha *prevista*, no la real:
        // el cierre la sustituye siempre por la de hoy (D4.4), así que
        // dejarla puesta no compromete nada.
        if ($periodo->estado !== EstadoPeriodo::Abierto) {
            return [
                'notas' => ['nullable', 'string', 'max:2000'],
                'fecha_inicio' => ['prohibited'],
                'fecha_fin' => ['prohibited'],
            ];
        }

        return [
            'notas' => ['nullable', 'string', 'max:2000'],
            'fecha_inicio' => ['sometimes', 'required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_inicio.prohibited' => __('periodos.validacion.no_editable_cerrado'),
            'fecha_fin.prohibited' => __('periodos.validacion.no_editable_cerrado'),
        ];
    }

    public function proyecto(): Proyecto
    {
        /** @var Proyecto */
        return $this->route('proyecto');
    }

    public function periodo(): ?Periodo
    {
        $periodo = $this->route('periodo');

        return $periodo instanceof Periodo ? $periodo : null;
    }

    /**
     * @return array{fecha_inicio: string, notas: string|null}
     */
    public function datosApertura(): array
    {
        return [
            'fecha_inicio' => (string) $this->validated('fecha_inicio'),
            'notas' => $this->validated('notas'),
        ];
    }
}
