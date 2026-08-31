<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\CicloEnElArbol;
use App\Models\Modulo;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;

/**
 * Aplica una reordenación del árbol de módulos en **una sola transacción**.
 *
 * Recibe los niveles afectados completos —cada padre con el orden de todos sus
 * hijos— y no un movimiento por nodo. Diez peticiones seguidas dejan estados
 * intermedios inconsistentes si una falla a la mitad; una transacción, o entra
 * entera o no entra nada.
 */
final class ReordenarModulos
{
    /**
     * @param  array<int, array{parent_id: int|null, modulos: array<int, int>}>  $niveles
     *
     * @throws CicloEnElArbol
     */
    public function __invoke(Proyecto $proyecto, array $niveles): void
    {
        $padrePorModulo = $proyecto->modulos()->pluck('parent_id', 'id')->all();

        // Se simula el resultado antes de tocar nada: comprobar cada nivel por
        // separado dejaría pasar un ciclo que solo aparece al aplicarlos todos.
        $resultante = $padrePorModulo;

        foreach ($niveles as $nivel) {
            foreach ($nivel['modulos'] as $id) {
                $resultante[$id] = $nivel['parent_id'];
            }
        }

        self::verificarSinCiclos($resultante);

        DB::transaction(function () use ($proyecto, $niveles): void {
            foreach ($niveles as $nivel) {
                foreach ($nivel['modulos'] as $posicion => $id) {
                    $proyecto->modulos()
                        ->whereKey($id)
                        ->update(['parent_id' => $nivel['parent_id'], 'orden' => $posicion]);
                }
            }
        });
    }

    /**
     * Un módulo no puede acabar dentro de uno de sus propios descendientes.
     *
     * Es el error clásico del árbol autoreferencial: la rama se desengancha del
     * resto y el componente recursivo entra en bucle al renderizarla. Se valida
     * aquí, en el servidor, y no solo en la interfaz — el arrastre no es el
     * único camino hasta esta acción.
     *
     * @param  array<int, int|null>  $padrePorModulo
     *
     * @throws CicloEnElArbol
     */
    private static function verificarSinCiclos(array $padrePorModulo): void
    {
        foreach (array_keys($padrePorModulo) as $inicio) {
            $visitados = [];
            $actual = $inicio;

            while ($actual !== null) {
                if (isset($visitados[$actual])) {
                    throw CicloEnElArbol::en(Modulo::class, (int) $inicio);
                }

                $visitados[$actual] = true;
                $actual = $padrePorModulo[$actual] ?? null;
            }
        }
    }
}
