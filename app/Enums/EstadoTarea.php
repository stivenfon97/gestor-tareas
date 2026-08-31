<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Punto del ciclo de vida de una tarea. Uno a la vez.
 *
 * Es el eje independiente de `TipoTarea` y `PrioridadTarea`: una tarea de tipo
 * `correccion` recorre este mismo ciclo completo.
 */
enum EstadoTarea: string
{
    case Pendiente = 'pendiente';
    case EnProceso = 'en_proceso';
    case EnEspera = 'en_espera';
    case Terminada = 'terminada';
    case Entregada = 'entregada';
    case Cancelada = 'cancelada';

    public function etiqueta(): string
    {
        return 'enums.estado_tarea.'.$this->value;
    }

    public function token(): string
    {
        return 'estado-'.str_replace('_', '-', $this->value);
    }

    /**
     * Estados a los que se puede pasar desde este.
     *
     * Fuente de verdad de la matriz de once transiciones. Debe coincidir con
     * `docs/diagramas/ciclo-de-vida-tarea.md` y con la skill `estados-de-tarea`;
     * `tests/Unit/MatrizDeTransicionesTest.php` lo comprueba por script.
     *
     * @return array<int, self>
     */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::Pendiente => [self::EnProceso, self::EnEspera, self::Cancelada],
            self::EnProceso => [self::EnEspera, self::Terminada, self::Cancelada],
            self::EnEspera => [self::Pendiente, self::EnProceso, self::Cancelada],
            self::Terminada => [self::EnProceso, self::Entregada],
            self::Entregada, self::Cancelada => [],
        };
    }

    public function puedeTransicionarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), strict: true);
    }

    /**
     * Un estado terminal no admite ninguna salida: un fallo posterior crea una
     * tarea nueva de tipo `correccion`, no reabre esta.
     */
    public function esTerminal(): bool
    {
        return match ($this) {
            self::Entregada, self::Cancelada => true,
            default => false,
        };
    }

    /**
     * Motivo que la transición hacia este estado exige, si lo hay.
     */
    public function motivoRequerido(): ?string
    {
        return match ($this) {
            self::EnEspera => 'motivo_espera',
            self::Cancelada => 'motivo_cancelacion',
            default => null,
        };
    }
}
