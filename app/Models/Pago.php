<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrigenTasaCambio;
use App\Observers\PagoObserver;
use Database\Factories\PagoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * El cobro de un periodo. Monto en USD, tasa de cambio y monto en COP.
 *
 * `monto_cop` se persiste calculado, no derivado en lectura: corregir la TRM
 * mañana no debe reescribir lo que ya se cobró.
 *
 * @property int $id
 * @property int $periodo_id
 * @property Carbon $fecha_pago
 * @property string $monto_usd
 * @property string $tasa_cambio
 * @property string $monto_cop
 * @property OrigenTasaCambio $origen_tasa
 * @property string|null $referencia
 * @property string|null $notas
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['periodo_id', 'fecha_pago', 'monto_usd', 'tasa_cambio', 'origen_tasa', 'referencia', 'notas'])]
#[ObservedBy([PagoObserver::class])]
class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory;

    protected $table = 'pagos';

    /**
     * Los montos se castean a `decimal`, nunca a `float`: la aritmética binaria
     * de coma flotante pierde centavos y los reportes dejan de cuadrar.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_pago' => 'date',
            'monto_usd' => 'decimal:2',
            'tasa_cambio' => 'decimal:4',
            'monto_cop' => 'decimal:2',
            'origen_tasa' => OrigenTasaCambio::class,
        ];
    }

    /** @return BelongsTo<Periodo, $this> */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }
}
