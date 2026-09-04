<?php

declare(strict_types=1);

use App\Enums\EstadoPeriodo;
use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\TipoReporte;
use App\Models\Cliente;
use App\Models\Modulo;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reporte;
use App\Models\Reunion;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

/**
 * El recorrido completo de aceptación de la fase 12: crear un cliente, un
 * proyecto, sus módulos, tareas por captura rápida, moverlas en el tablero,
 * registrar una reunión con acuerdos y crear tareas desde ella, cerrar el
 * periodo trasladando lo pendiente, registrar el pago y generar los dos
 * reportes — la misma secuencia que el prompt de cierre pide recorrer "a
 * mano, como usuario".
 *
 * Va por HTTP real contra las rutas reales, en el orden en que un usuario lo
 * haría, y comprueba el estado de la base de datos y de los archivos
 * generados en cada paso — no solo códigos 200/302. Es la prueba automatizada
 * equivalente al recorrido manual: esta sesión no tiene control de
 * navegador, así que esto es lo que sí puede verificar de punta a punta sin
 * simular nada.
 */
it('recorre el flujo completo: cliente, proyecto, módulos, tareas, tablero, reunión, cierre, pago y los dos reportes', function () {
    $this->seed(RolesYPermisosSeeder::class);

    $lider = usuarioCon(RolGlobal::Miembro);
    // Crear clientes y proyectos exige el permiso global que solo tiene el
    // administrador (ClientePolicy@create, ProyectoPolicy@create): un
    // Miembro corriente —el rol que tiene un líder de proyecto— no puede.
    $administrador = usuarioCon(RolGlobal::Administrador);

    // 1. Crear un cliente.
    $this->actingAs($administrador)
        ->post(route('clientes.store'), [
            'nombre' => 'Óptica Visión Clara S.A.S.',
            'contacto_nombre' => 'Marta Gómez',
            'contacto_email' => 'marta@opticavisionclara.co',
        ])
        ->assertRedirect();

    $cliente = Cliente::query()->where('nombre', 'Óptica Visión Clara S.A.S.')->sole();

    // 2. Crear un proyecto para ese cliente. El líder se asigna al proyecto
    // justo después, que es como se hace en la aplicación real desde la
    // pantalla de miembros.
    $this->actingAs($administrador)
        ->post(route('proyectos.store'), [
            'cliente_id' => $cliente->id,
            'nombre' => 'Portal de Citas',
            'prefijo_tarea' => 'CIT',
            'descripcion' => 'Agenda de citas en línea para la óptica.',
            'estado' => 'activo',
        ])
        ->assertRedirect();

    $proyecto = Proyecto::query()->where('prefijo_tarea', 'CIT')->sole();

    asignarAProyecto($lider, $proyecto, RolProyecto::Lider);

    // Un periodo abierto antes de crear tareas: la captura rápida asigna
    // cada tarea nueva al periodo abierto del proyecto (CrearTareasEnLote).
    $this->actingAs($lider)
        ->post(route('proyectos.periodos.store', $proyecto), [
            'fecha_inicio' => '2026-01-05',
            'notas' => 'Arranque del proyecto.',
        ])
        ->assertRedirect();

    $periodo = Periodo::query()->where('proyecto_id', $proyecto->id)->sole();
    expect($periodo->consecutivo)->toBe(1)
        ->and($periodo->estado)->toBe(EstadoPeriodo::Abierto);

    // 3. Módulos: uno raíz y un submódulo colgado de él.
    $this->actingAs($lider)
        ->post(route('proyectos.modulos.store', $proyecto), ['nombre' => 'Agenda'])
        ->assertRedirect();

    $agenda = Modulo::query()->where('proyecto_id', $proyecto->id)->where('nombre', 'Agenda')->sole();

    $this->actingAs($lider)
        ->post(route('proyectos.modulos.store', $proyecto), [
            'nombre' => 'Recordatorios',
            'parent_id' => $agenda->id,
        ])
        ->assertRedirect();

    $recordatorios = Modulo::query()->where('proyecto_id', $proyecto->id)->where('nombre', 'Recordatorios')->sole();
    expect($recordatorios->parent_id)->toBe($agenda->id);

    // 4. Tareas por captura rápida: varias líneas en un solo lote, algunas
    // con módulo existente y una que crea un módulo nuevo sobre la marcha.
    $this->actingAs($lider)
        ->post(route('proyectos.tareas.rapida.store', $proyecto), [
            'filas' => [
                ['nombre' => 'Formulario de reserva de cita', 'estado' => 'pendiente', 'tipo' => 'desarrollo', 'modulo_id' => $agenda->id],
                ['nombre' => 'Enviar recordatorio por correo 24h antes', 'estado' => 'pendiente', 'tipo' => 'desarrollo', 'modulo_id' => $recordatorios->id],
                ['nombre' => 'Panel de disponibilidad del optometrista', 'estado' => 'pendiente', 'tipo' => 'desarrollo', 'modulo_nuevo' => 'Disponibilidad'],
            ],
        ])
        ->assertRedirect(route('proyectos.tareas.index', $proyecto));

    expect(Tarea::query()->where('proyecto_id', $proyecto->id)->count())->toBe(3);

    $formulario = Tarea::query()->where('nombre', 'Formulario de reserva de cita')->sole();
    $recordatorio = Tarea::query()->where('nombre', 'Enviar recordatorio por correo 24h antes')->sole();
    $panel = Tarea::query()->where('nombre', 'Panel de disponibilidad del optometrista')->sole();

    foreach ([$formulario, $recordatorio, $panel] as $tarea) {
        expect($tarea->periodo_id)->toBe($periodo->id)
            ->and($tarea->codigo)->toStartWith('CIT-');
    }

    // 5. Moverlas en el tablero: arrastrar una tarjeta entre columnas de
    // estado es, en HTTP, PUT tareas/{tarea}/estado (EstadoTareaController).
    $this->actingAs($lider)->put(route('tareas.estado.update', $formulario), ['estado' => 'en_proceso'])->assertRedirect();
    $this->actingAs($lider)->put(route('tareas.estado.update', $recordatorio), ['estado' => 'en_proceso'])->assertRedirect();

    expect($formulario->fresh()->estado)->toBe(EstadoTarea::EnProceso)
        ->and($recordatorio->fresh()->estado)->toBe(EstadoTarea::EnProceso)
        // fecha_inicio se fija en la primera entrada a en_proceso (D2.13).
        ->and($formulario->fresh()->fecha_inicio)->not->toBeNull();

    $this->actingAs($lider)->put(route('tareas.estado.update', $formulario), ['estado' => 'terminada'])->assertRedirect();
    $this->actingAs($lider)->put(route('tareas.estado.update', $formulario), ['estado' => 'entregada'])->assertRedirect();

    expect($formulario->fresh()->estado)->toBe(EstadoTarea::Entregada);

    // 6. Reunión con acuerdos, y tareas creadas desde ella.
    $this->actingAs($lider)
        ->post(route('proyectos.reuniones.store', $proyecto), datosDeReunionValidos([
            'titulo' => 'Revisión de alcance con la clínica',
            'acuerdos' => 'Se agrega recordatorio por WhatsApp además del correo. La clínica confirma el horario de atención: 7am-7pm.',
        ]))
        ->assertRedirect();

    $reunion = Reunion::query()->where('proyecto_id', $proyecto->id)->sole();
    expect($reunion->acuerdos)->toContain('WhatsApp');

    $this->actingAs($lider)
        ->post(route('reuniones.tareas.rapida.previsualizar', $reunion), [
            'texto' => 'Recordatorio de cita por WhatsApp | pendiente',
        ])
        ->assertRedirect()
        ->assertInertiaFlash('previsualizacion');

    $this->actingAs($lider)->post(route('reuniones.tareas.rapida.crear', $reunion), [
        'filas' => [[
            'nombre' => 'Recordatorio de cita por WhatsApp',
            'estado' => 'pendiente',
            'tipo' => 'desarrollo',
            'descripcion' => null,
            'prioridad' => null,
            'modulo_id' => $recordatorios->id,
            'modulo_nuevo' => null,
            'motivo_espera' => null,
            'motivo_cancelacion' => null,
        ]],
    ])->assertRedirect();

    $desdeReunion = Tarea::query()->where('nombre', 'Recordatorio de cita por WhatsApp')->sole();
    expect($reunion->fresh()->tareas()->pluck('tareas.id')->all())->toBe([$desdeReunion->id])
        ->and($desdeReunion->periodo_id)->toBe($periodo->id);

    // 7. Cerrar el periodo, trasladando lo que sigue pendiente. `entregada`
    // es terminal (D2.6) y no se traslada; el resto de tareas no terminales,
    // sí.
    $pendientesAntesDeCerrar = Tarea::query()
        ->where('periodo_id', $periodo->id)
        ->whereNotIn('estado', [EstadoTarea::Entregada, EstadoTarea::Cancelada])
        ->pluck('id')
        ->all();

    expect($pendientesAntesDeCerrar)->toHaveCount(3); // recordatorio, panel, desdeReunion

    $this->actingAs($lider)
        ->post(route('periodos.cierre.store', $periodo), [
            'tareas_trasladar' => $pendientesAntesDeCerrar,
            'fecha_inicio_siguiente' => '2026-02-01',
        ])
        ->assertRedirect();

    expect($periodo->fresh()->estado)->toBe(EstadoPeriodo::Cerrado);

    $periodoDos = Periodo::query()->where('proyecto_id', $proyecto->id)->where('consecutivo', 2)->sole();

    foreach ([$recordatorio, $panel, $desdeReunion] as $tarea) {
        expect($tarea->fresh()->periodo_id)->toBe($periodoDos->id)
            ->and($tarea->fresh()->periodo_origen_id)->toBe($periodo->id);
    }
    // La entregada se queda en el periodo donde se entregó de verdad.
    expect($formulario->fresh()->periodo_id)->toBe($periodo->id);

    // 8. Registrar el pago del periodo ya cerrado.
    $this->actingAs($lider)
        ->post(route('proyectos.pagos.store', $proyecto), [
            'periodo_id' => $periodo->id,
            'fecha_pago' => '2026-02-03',
            'monto_usd' => '1500.00',
            'tasa_cambio' => '4100.0000',
            'origen_tasa' => 'manual',
            'referencia' => 'TRF-CIT-001',
        ])
        ->assertRedirect();

    $pago = $periodo->fresh()->pago;
    expect($pago)->not->toBeNull()
        ->and($pago->monto_usd)->toBe('1500.00')
        ->and($pago->monto_cop)->toBe('6150000.00');

    // 9. Generar los dos reportes del periodo cerrado, y descargar los dos.
    $this->actingAs($lider)->post(route('proyectos.reportes.store', $proyecto), [
        'periodo_id' => $periodo->id,
        'tipo' => 'completo',
    ])->assertRedirect();

    $this->actingAs($lider)->post(route('proyectos.reportes.store', $proyecto), [
        'periodo_id' => $periodo->id,
        'tipo' => 'cliente',
    ])->assertRedirect();

    $reporteCompleto = Reporte::query()->where('periodo_id', $periodo->id)->where('tipo', TipoReporte::Completo)->sole();
    $reporteCliente = Reporte::query()->where('periodo_id', $periodo->id)->where('tipo', TipoReporte::Cliente)->sole();

    $libroCompleto = leerExcelDescargado($this->actingAs($lider)->get(route('reportes.descargar', $reporteCompleto)));
    $libroCliente = leerExcelDescargado($this->actingAs($lider)->get(route('reportes.descargar', $reporteCliente)));

    // El completo lleva cuatro hojas (resumen, tareas, notas, pagos); el de
    // cliente, una sola (D7.1).
    expect($libroCompleto->getSheetCount())->toBe(4)
        ->and($libroCliente->getSheetCount())->toBe(1);

    // La tarea entregada en este periodo aparece en el reporte completo.
    $nombresEnCompleto = collect($libroCompleto->getSheetByName(__('reportes.hojas.tareas'))?->toArray() ?? [])
        ->flatten()
        ->filter(fn ($valor) => is_string($valor));

    expect($nombresEnCompleto->contains('Formulario de reserva de cita'))->toBeTrue();
});
