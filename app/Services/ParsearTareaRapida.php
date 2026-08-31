<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Models\Modulo;
use App\Models\Proyecto;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Convierte texto pegado a granel en tareas propuestas.
 *
 * Formato: `nombre | estado | descripción | módulo`. **Solo el nombre es
 * obligatorio**; los otros tres campos son opcionales y posicionales.
 *
 * Vive en el servidor y no en Vue por dos razones: se puede probar de verdad, y
 * un futuro import desde archivo lo reutiliza sin reescribirlo.
 *
 * El parser **nunca rechaza una línea**. Un estado irreconocible cae a
 * `pendiente` con un aviso, y un módulo que no existe se propone crear. Rechazar
 * la línea obligaría al usuario a corregir el texto original y volver a pegar,
 * que es justo la fricción que hace que la captura rápida se abandone.
 */
final class ParsearTareaRapida
{
    private const SEPARADOR = '|';

    /**
     * Una fila por línea no vacía del texto.
     *
     * @return array<int, array{
     *     linea: int,
     *     original: string,
     *     nombre: string,
     *     estado: string,
     *     descripcion: string|null,
     *     modulo: string|null,
     *     modulo_id: int|null,
     *     modulo_por_crear: bool,
     *     requiere_motivo: string|null,
     *     avisos: array<int, string>
     * }>
     */
    public function __invoke(Proyecto $proyecto, string $texto): array
    {
        $modulos = $proyecto->modulos()->get(['id', 'nombre'])
            ->mapWithKeys(fn (Modulo $modulo): array => [
                self::normalizar($modulo->nombre) => $modulo->id,
            ]);

        $filas = [];
        $numero = 0;

        foreach (preg_split('/\R/u', $texto) ?: [] as $linea) {
            $numero++;

            // Las líneas en blanco se saltan sin avisar: pegar desde un correo
            // arrastra separaciones que no significan nada.
            if (trim($linea) === '') {
                continue;
            }

            $filas[] = $this->fila($numero, $linea, $modulos);
        }

        return $filas;
    }

    /**
     * @param  Collection<string, int>  $modulos
     * @return array<string, mixed>
     */
    private function fila(int $numero, string $linea, $modulos): array
    {
        $avisos = [];

        $partes = array_map(trim(...), explode(self::SEPARADOR, trim($linea)));
        $total = count($partes);

        $nombre = $partes[0] ?? '';
        $estadoCrudo = $partes[1] ?? '';

        /*
         * El módulo se toma **desde el final** y la descripción se queda con
         * todo lo que sobre en medio, separadores incluidos.
         *
         * Partir por posición desde la izquierda haría que "el total sale mal |
         * y también con IVA" —una descripción perfectamente normal— acabara
         * proponiendo crear un módulo llamado "y también con IVA". Una
         * descripción con `|` es frecuente; un nombre de módulo con `|` no
         * existe.
         */
        if ($total >= 4) {
            $moduloCrudo = $partes[$total - 1];
            $descripcion = implode(' '.self::SEPARADOR.' ', array_slice($partes, 2, $total - 3));
        } else {
            $descripcion = $partes[2] ?? '';
            $moduloCrudo = '';
        }

        if ($nombre === '') {
            // El nombre es lo único obligatorio; sin él la línea no describe
            // ninguna tarea.
            $avisos[] = 'tareas.rapida.avisos.sin_nombre';
        }

        $estado = EstadoTarea::Pendiente;

        if ($estadoCrudo !== '') {
            $reconocido = self::reconocerEstado($estadoCrudo);

            if ($reconocido === null) {
                $avisos[] = 'tareas.rapida.avisos.estado_desconocido';
            } else {
                $estado = $reconocido;
            }
        }

        $moduloId = null;
        $moduloPorCrear = false;

        if ($moduloCrudo !== '') {
            $moduloId = $modulos[self::normalizar($moduloCrudo)] ?? null;

            if ($moduloId === null) {
                // No se falla: se ofrece crearlo. El usuario acaba de escribir
                // el nombre del módulo, así que ya sabe cuál quiere.
                $moduloPorCrear = true;
                $avisos[] = 'tareas.rapida.avisos.modulo_nuevo';
            }
        }

        /*
         * `en_espera` y `cancelada` exigen su motivo también al crear (D2.9), y
         * el observer aborta el guardado sin él. En vez de degradar la línea en
         * silencio, se marca aquí y la previsualización pide el texto: es
         * exactamente el momento en que el usuario todavía recuerda por qué lo
         * escribió.
         */
        $requiereMotivo = $estado->motivoRequerido();

        if ($requiereMotivo !== null) {
            $avisos[] = 'tareas.rapida.avisos.requiere_motivo';
        }

        return [
            'linea' => $numero,
            'original' => trim($linea),
            'nombre' => $nombre,
            'estado' => $estado->value,
            'descripcion' => $descripcion === '' ? null : $descripcion,
            'modulo' => $moduloCrudo === '' ? null : $moduloCrudo,
            'modulo_id' => $moduloId,
            'modulo_por_crear' => $moduloPorCrear,
            'requiere_motivo' => $requiereMotivo,
            'avisos' => $avisos,
        ];
    }

    /**
     * Tolerante con acentos, mayúsculas, espacios y guiones bajos: `En Proceso`,
     * `en proceso` y `EN_PROCESO` son el mismo estado.
     */
    public static function reconocerEstado(string $crudo): ?EstadoTarea
    {
        $normalizado = self::normalizar($crudo);

        foreach (EstadoTarea::cases() as $estado) {
            if (self::normalizar($estado->value) === $normalizado) {
                return $estado;
            }
        }

        return null;
    }

    /**
     * Minúsculas, sin acentos y con los separadores internos colapsados a un
     * espacio. `en_proceso`, `En Proceso` y `  EN  PROCESO ` acaban iguales.
     */
    private static function normalizar(string $valor): string
    {
        $sinAcentos = Str::ascii(trim($valor));
        $conEspacios = str_replace(['_', '-'], ' ', $sinAcentos);

        return Str::lower((string) preg_replace('/\s+/u', ' ', $conEspacios));
    }
}
