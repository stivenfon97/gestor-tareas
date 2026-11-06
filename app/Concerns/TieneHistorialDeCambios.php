<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\HistorialCambio;
use App\Observers\HistorialDeCambiosObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Engancha un modelo al historial de cambios.
 *
 * Registra `HistorialDeCambiosObserver` al arrancar el modelo, así que todo
 * guardado por Eloquent —controlador, comando, seeder, `tinker`— deja su fila
 * sin que nadie tenga que acordarse. Lo que **no** pasa por Eloquent (un
 * `update()` de query builder) no dispara eventos: esas escrituras llaman a
 * `RegistrarHistorial` a mano (`CerrarPeriodo`, `ReordenarModulos`).
 *
 * El modelo afina qué se registra sobrescribiendo dos métodos:
 * - `camposSinHistorial()`: no se registran nunca (ruido como `orden`).
 * - `camposOcultosEnHistorial()`: se registra que cambiaron, pero no su valor
 *   (la contraseña, o un HTML enriquecido que no se lee como diferencia).
 *
 * @phpstan-require-extends Model
 */
trait TieneHistorialDeCambios
{
    /**
     * Los eventos se registran uno a uno y no con `static::observe()`: este
     * método corre mientras el modelo arranca, y `observe()` instancia el
     * modelo, que vuelve a arrancarlo (`LogicException`).
     */
    public static function bootTieneHistorialDeCambios(): void
    {
        foreach (['created', 'updated', 'deleted', 'restored'] as $evento) {
            static::registerModelEvent(
                $evento,
                static fn (Model $registro) => app(HistorialDeCambiosObserver::class)->{$evento}($registro),
            );
        }
    }

    /**
     * Sin `morphMany` a propósito: el alias del tipo no sale del `morphMap`
     * global (ver `HistorialCambio::TIPOS`).
     *
     * @return HasMany<HistorialCambio, $this>
     */
    public function historialDeCambios(): HasMany
    {
        return $this->hasMany(HistorialCambio::class, 'registro_id')
            ->where('registro_type', HistorialCambio::aliasDe(static::class))
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    public function camposSinHistorial(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function camposOcultosEnHistorial(): array
    {
        return [];
    }
}
