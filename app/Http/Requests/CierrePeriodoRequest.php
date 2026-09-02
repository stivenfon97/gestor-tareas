<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EstadoTarea;
use App\Enums\OrigenTasaCambio;
use App\Models\Periodo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cierre de un periodo: traslado en lote, apertura del siguiente y pago
 * opcional, todo en la misma pantalla y la misma petición.
 */
class CierrePeriodoRequest extends FormRequest
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

        return [
            'tareas_trasladar' => ['array'],
            'tareas_trasladar.*' => [
                'integer',
                Rule::exists('tareas', 'id')
                    ->where('periodo_id', $periodo->getKey())
                    ->whereNotIn('estado', [EstadoTarea::Entregada->value, EstadoTarea::Cancelada->value]),
            ],
            'fecha_inicio_siguiente' => ['required', 'date'],
            'pago' => ['nullable', 'array'],
            'pago.fecha_pago' => ['required_with:pago', 'date'],
            'pago.monto_usd' => ['required_with:pago', 'numeric', 'min:0'],
            'pago.tasa_cambio' => ['required_with:pago', 'numeric', 'min:0'],
            'pago.origen_tasa' => ['required_with:pago', Rule::enum(OrigenTasaCambio::class)],
            'pago.referencia' => ['nullable', 'string', 'max:255'],
            'pago.notas' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function periodo(): Periodo
    {
        /** @var Periodo */
        return $this->route('periodo');
    }

    /**
     * @return array<int, int>
     */
    public function idsTrasladar(): array
    {
        return array_map('intval', $this->validated('tareas_trasladar', []));
    }

    /**
     * @return array{fecha_pago: string, monto_usd: string, tasa_cambio: string, origen_tasa: string, referencia: string|null, notas: string|null}|null
     */
    public function datosPago(): ?array
    {
        $pago = $this->validated('pago');

        if ($pago === null) {
            return null;
        }

        return [
            'fecha_pago' => $pago['fecha_pago'],
            'monto_usd' => (string) $pago['monto_usd'],
            'tasa_cambio' => (string) $pago['tasa_cambio'],
            'origen_tasa' => $pago['origen_tasa'],
            'referencia' => $pago['referencia'] ?? null,
            'notas' => $pago['notas'] ?? null,
        ];
    }
}
