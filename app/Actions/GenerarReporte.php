<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EstadoTarea;
use App\Enums\TipoReporte;
use App\Models\Modulo;
use App\Models\NotaTarea;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reporte;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Construye el snapshot desnormalizado de un periodo y deja el registro de
 * emisión.
 *
 * El snapshot guarda los valores **tal como salieron al Excel** -texto, no
 * ids-: si un módulo se renombra el mes siguiente, la descarga histórica de
 * este reporte sigue mostrando el nombre de hoy. Ver `docs/modulos/reportes.md`.
 *
 * El reporte de `cliente` construye un snapshot que **no contiene en
 * absoluto** resumen, notas ni pagos: no es que esas hojas no se muestren, es
 * que ese dato nunca entra en la fila que se guarda. Es la garantía real de
 * que no hay filtración posible, no una columna que se omite al exportar.
 */
final class GenerarReporte
{
    public function __invoke(Periodo $periodo, TipoReporte $tipo, User $usuario): Reporte
    {
        $periodo->loadMissing('proyecto');
        $proyecto = $periodo->proyecto;

        // `modulo` no se precarga: la ruta completa se resuelve aparte, de una
        // sola vez, contra `indiceDeRutas()`.
        $tareas = $periodo->tareas()
            ->with(['notas.usuario:id,name'])
            ->orderBy('orden')
            ->get();

        $rutaModulo = $this->indiceDeRutas($proyecto->id);

        $filasTareas = $tareas->map(fn (Tarea $tarea): array => $this->filaTarea($tarea, $rutaModulo));

        $conteos = $tareas->countBy(fn (Tarea $tarea): string => $tarea->estado->value);
        $resumenEstados = collect(EstadoTarea::cases())
            ->mapWithKeys(fn (EstadoTarea $estado): array => [$estado->value => $conteos->get($estado->value, 0)])
            ->all();

        $snapshot = $tipo === TipoReporte::Completo
            ? [
                'resumen' => $this->filasResumen($proyecto, $periodo, $tareas, $resumenEstados),
                'tareas' => $filasTareas->all(),
                'notas' => $this->filasNotas($tareas)->all(),
                'pagos' => $this->filasPagos($periodo),
            ]
            : [
                'tareas' => $filasTareas->map(fn (array $fila): array => [
                    'tarea' => $fila['tarea'],
                    'estado' => $fila['estado'],
                    'estado_etiqueta' => $fila['estado_etiqueta'],
                    'descripcion' => $fila['descripcion'],
                    'modulo' => $fila['modulo'],
                ])->all(),
            ];

        $nombreArchivo = sprintf(
            '%s_P%d_%s_%s.xlsx',
            $proyecto->prefijo_tarea,
            $periodo->consecutivo,
            now()->toDateString(),
            $tipo->sufijoArchivo(),
        );

        return Reporte::query()->create([
            'proyecto_id' => $proyecto->id,
            'periodo_id' => $periodo->id,
            'tipo' => $tipo,
            'nombre_archivo' => $nombreArchivo,
            'filtros' => ['periodo_id' => $periodo->id, 'tipo' => $tipo->value],
            'resumen_estados' => $resumenEstados,
            'snapshot' => $snapshot,
            'total_tareas' => $tareas->count(),
            'hash' => sha1(json_encode($snapshot, JSON_THROW_ON_ERROR)),
            'generado_por' => $usuario->id,
            'generado_en' => Carbon::now(),
        ]);
    }

    /**
     * Ruta completa de cada módulo del proyecto, resuelta con una sola
     * consulta.
     *
     * `Modulo::rutaCompleta()` recorre `padre` por Eloquent y sin precarga
     * compartida repetiría la consulta por cada tarea que cuelgue del mismo
     * módulo. Aquí se resuelve una vez, en memoria, contra un índice por id.
     *
     * @return array<int, string>
     */
    private function indiceDeRutas(int $proyectoId): array
    {
        $modulos = Modulo::query()
            ->where('proyecto_id', $proyectoId)
            ->get(['id', 'parent_id', 'nombre'])
            ->keyBy('id');

        $ruta = function (?int $id) use (&$ruta, $modulos): string {
            if ($id === null || ! $modulos->has($id)) {
                return '';
            }

            /** @var Modulo $modulo */
            $modulo = $modulos->get($id);
            $padre = $ruta($modulo->parent_id);

            return $padre === '' ? $modulo->nombre : $padre.' › '.$modulo->nombre;
        };

        return $modulos->keys()->mapWithKeys(fn (int $id): array => [$id => $ruta($id)])->all();
    }

    /**
     * @param  array<int, string>  $rutaModulo
     * @return array{codigo: string, tipo: string, modulo: string, tarea: string, estado: string, estado_etiqueta: string, prioridad: string, descripcion: string, fecha_inicio: ?string, fecha_fin: ?string, arrastrada: string, notas: string}
     */
    private function filaTarea(Tarea $tarea, array $rutaModulo): array
    {
        return [
            'codigo' => $tarea->codigo,
            'tipo' => __($tarea->tipo->etiqueta()),
            'modulo' => $tarea->modulo_id !== null ? ($rutaModulo[$tarea->modulo_id] ?? '') : '',
            'tarea' => $tarea->nombre,
            'estado' => $tarea->estado->value,
            'estado_etiqueta' => __($tarea->estado->etiqueta()),
            'prioridad' => __($tarea->prioridad->etiqueta()),
            'descripcion' => (string) $tarea->descripcion,
            'fecha_inicio' => $tarea->fecha_inicio?->toDateString(),
            'fecha_fin' => $tarea->fecha_fin?->toDateString(),
            'arrastrada' => $tarea->estaArrastrada() ? __('reportes.si') : __('reportes.no'),
            // Resumen condensado para leer de un vistazo; el detalle completo
            // -fecha y autor de cada una- vive en la hoja «Notas».
            'notas' => $tarea->notas->pluck('contenido')->implode(' · '),
        ];
    }

    /**
     * @param  Collection<int, Tarea>  $tareas
     * @return Collection<int, array{codigo: string, fecha: ?string, autor: string, nota: string}>
     */
    private function filasNotas(Collection $tareas): Collection
    {
        return $tareas->flatMap(fn (Tarea $tarea): Collection => $tarea->notas->map(fn (NotaTarea $nota): array => [
            'codigo' => $tarea->codigo,
            'fecha' => $nota->created_at?->toDateString(),
            'autor' => $nota->usuario->name,
            'nota' => $nota->contenido,
        ]))->values();
    }

    /**
     * Un periodo tiene como mucho un pago (D4.2): la fila sale vacía, nunca
     * rota, cuando todavía no se ha registrado.
     *
     * @return array<int, array{fecha: string, monto_usd: float, tasa_cambio: float, monto_cop: float, origen_tasa: string, referencia: string}>
     */
    private function filasPagos(Periodo $periodo): array
    {
        $pago = $periodo->pago;

        if ($pago === null) {
            return [];
        }

        return [[
            'fecha' => $pago->fecha_pago->toDateString(),
            'monto_usd' => (float) $pago->monto_usd,
            'tasa_cambio' => (float) $pago->tasa_cambio,
            'monto_cop' => (float) $pago->monto_cop,
            'origen_tasa' => __($pago->origen_tasa->etiqueta()),
            'referencia' => (string) ($pago->referencia ?? ''),
        ]];
    }

    /**
     * @param  Collection<int, Tarea>  $tareas
     * @param  array<string, int>  $resumenEstados
     * @return array<int, array{etiqueta: string, valor: mixed, tipo: string}>
     */
    private function filasResumen(
        Proyecto $proyecto,
        Periodo $periodo,
        Collection $tareas,
        array $resumenEstados,
    ): array {
        $filas = [
            ['etiqueta' => __('reportes.resumen.cliente'), 'valor' => $proyecto->cliente->nombre, 'tipo' => 'texto'],
            ['etiqueta' => __('reportes.resumen.proyecto'), 'valor' => $proyecto->nombre, 'tipo' => 'texto'],
            ['etiqueta' => __('reportes.resumen.periodo'), 'valor' => 'P'.$periodo->consecutivo, 'tipo' => 'texto'],
            ['etiqueta' => __('reportes.resumen.fecha_inicio'), 'valor' => $periodo->fecha_inicio->toDateString(), 'tipo' => 'fecha'],
            ['etiqueta' => __('reportes.resumen.fecha_fin'), 'valor' => $periodo->fecha_fin?->toDateString(), 'tipo' => $periodo->fecha_fin === null ? 'texto' : 'fecha'],
            ['etiqueta' => __('reportes.resumen.total_tareas'), 'valor' => $tareas->count(), 'tipo' => 'numero'],
        ];

        foreach (EstadoTarea::cases() as $estado) {
            $filas[] = [
                'etiqueta' => __($estado->etiqueta()),
                'valor' => $resumenEstados[$estado->value],
                'tipo' => 'numero',
            ];
        }

        $filas[] = [
            'etiqueta' => __('reportes.resumen.arrastradas'),
            'valor' => $tareas->filter(fn (Tarea $tarea): bool => $tarea->estaArrastrada())->count(),
            'tipo' => 'numero',
        ];
        $filas[] = ['etiqueta' => __('reportes.resumen.fecha_generacion'), 'valor' => now()->toDateString(), 'tipo' => 'fecha'];

        return $filas;
    }
}
