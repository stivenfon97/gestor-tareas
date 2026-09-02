<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\OrigenTasaCambio;
use App\Models\Pago;
use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registrar y editar un pago.
 *
 * `periodo_id` solo se acepta al crear: un pago no cambia de periodo, cambia
 * de pago. El `unique` de `pagos.periodo_id` es la cerradura real; aquí solo
 * se adelanta con un 422 legible.
 */
class PagoRequest extends FormRequest
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
        $pago = $this->pago();

        $reglas = [
            'fecha_pago' => ['required', 'date'],
            'monto_usd' => ['required', 'numeric', 'min:0'],
            'tasa_cambio' => ['required', 'numeric', 'min:0'],
            'origen_tasa' => ['required', Rule::enum(OrigenTasaCambio::class)],
            'referencia' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];

        if (! $pago instanceof Pago) {
            $reglas['periodo_id'] = [
                'required',
                'integer',
                Rule::exists('periodos', 'id')->where('proyecto_id', $this->proyecto()->getKey()),
                Rule::unique('pagos', 'periodo_id'),
            ];
        }

        return $reglas;
    }

    public function proyecto(): Proyecto
    {
        /** @var Proyecto */
        return $this->route('proyecto');
    }

    public function pago(): ?Pago
    {
        $pago = $this->route('pago');

        return $pago instanceof Pago ? $pago : null;
    }
}
