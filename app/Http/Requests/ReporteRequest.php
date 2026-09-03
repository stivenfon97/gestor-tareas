<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TipoReporte;
use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Generar un reporte: elige periodo y tipo, nada más. El resto de la fila
 * -snapshot, hash, resumen por estado- se calcula del lado del servidor.
 */
class ReporteRequest extends FormRequest
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
            'periodo_id' => [
                'required',
                'integer',
                Rule::exists('periodos', 'id')->where('proyecto_id', $this->proyecto()->getKey()),
            ],
            'tipo' => ['required', Rule::enum(TipoReporte::class)],
        ];
    }

    public function proyecto(): Proyecto
    {
        /** @var Proyecto */
        return $this->route('proyecto');
    }
}
