<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoReporte;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Registro permanente de la emisión de un reporte.
 *
 * Los `.xlsx` no se persisten en disco: se generan bajo demanda. `snapshot`
 * guarda las filas exactas exportadas y es lo único que permite reconstruir el
 * archivo idéntico al que se envió. Sin él, regenerar en noviembre el reporte
 * de julio daría un archivo distinto y la trazabilidad sería ficticia.
 *
 * Por eso **no hay columna de ruta de archivo**. No la añadas.
 *
 * @property int $id
 * @property int $proyecto_id
 * @property int $periodo_id
 * @property TipoReporte $tipo
 * @property string $nombre_archivo
 * @property array<string, mixed> $filtros
 * @property array<string, int> $resumen_estados
 * @property array<int, array<string, mixed>> $snapshot
 * @property int $total_tareas
 * @property string $hash
 * @property int $generado_por
 * @property Carbon $generado_en
 */
#[Fillable([
    'proyecto_id',
    'periodo_id',
    'tipo',
    'nombre_archivo',
    'filtros',
    'resumen_estados',
    'snapshot',
    'total_tareas',
    'hash',
    'generado_por',
    'generado_en',
])]
class Reporte extends Model
{
    protected $table = 'reportes';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoReporte::class,
            'filtros' => 'array',
            'resumen_estados' => 'array',
            'snapshot' => 'array',
            'total_tareas' => 'integer',
            'generado_en' => 'datetime',
        ];
    }

    /** @return BelongsTo<Proyecto, $this> */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /** @return BelongsTo<Periodo, $this> */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }
}
