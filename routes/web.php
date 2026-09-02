<?php

declare(strict_types=1);

use App\Http\Controllers\AgendaDeTareasController;
use App\Http\Controllers\ArchivoController;
use App\Http\Controllers\AsignarEquipoAProyectoController;
use App\Http\Controllers\CapturaRapidaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EstadoClienteController;
use App\Http\Controllers\EstadoTareaController;
use App\Http\Controllers\Gestion\EquipoController;
use App\Http\Controllers\Gestion\EstadoUsuarioController;
use App\Http\Controllers\Gestion\MiembroEquipoController;
use App\Http\Controllers\Gestion\UsuarioController;
use App\Http\Controllers\ListaAgrupadaController;
use App\Http\Controllers\ListadoDeTareasController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\MoverTareaEnTableroController;
use App\Http\Controllers\NotaTareaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ProyectoUsuarioController;
use App\Http\Controllers\ReordenarModulosController;
use App\Http\Controllers\ReordenarTareasController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\TableroController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\VistaGuardadaController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    /*
     * Gestión de usuarios: solo quien tiene `usuarios.gestionar`, que hoy es el
     * administrador. No hay registro público.
     */
    Route::middleware('can:usuarios.gestionar')->group(function () {
        Route::get('usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');

        // Se desactiva, no se borra: `creado_por` es `restrict`.
        Route::put('usuarios/{usuario}/estado', [EstadoUsuarioController::class, 'update'])->name('usuarios.estado.update');
    });

    // Equipos: autorizados por `EquipoPolicy`.
    Route::get('equipos', [EquipoController::class, 'index'])->name('equipos.index');
    Route::get('equipos/crear', [EquipoController::class, 'create'])->name('equipos.create');
    Route::post('equipos', [EquipoController::class, 'store'])->name('equipos.store');
    Route::get('equipos/{equipo}/editar', [EquipoController::class, 'edit'])->name('equipos.edit');
    Route::put('equipos/{equipo}', [EquipoController::class, 'update'])->name('equipos.update');
    Route::delete('equipos/{equipo}', [EquipoController::class, 'destroy'])->name('equipos.destroy');
    Route::post('equipos/{equipo}/miembros', [MiembroEquipoController::class, 'store'])->name('equipos.miembros.store');
    Route::delete('equipos/{equipo}/miembros/{usuario}', [MiembroEquipoController::class, 'destroy'])->name('equipos.miembros.destroy');

    /*
     * Clientes: catálogo global, autorizado por `ClientePolicy`. Nunca se
     * borran —`proyectos.cliente_id` es `restrict`—, se desactivan.
     */
    Route::get('clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('clientes/crear', [ClienteController::class, 'create'])->name('clientes.create');
    Route::post('clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::get('clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
    Route::get('clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::put('clientes/{cliente}/estado', [EstadoClienteController::class, 'update'])->name('clientes.estado.update');

    // Proyectos y su membresía.
    Route::get('proyectos', [ProyectoController::class, 'index'])->name('proyectos.index');
    Route::get('proyectos/crear', [ProyectoController::class, 'create'])->name('proyectos.create');
    Route::post('proyectos', [ProyectoController::class, 'store'])->name('proyectos.store');
    Route::get('proyectos/{proyecto}', [ProyectoController::class, 'show'])->name('proyectos.show');
    Route::get('proyectos/{proyecto}/editar', [ProyectoController::class, 'edit'])->name('proyectos.edit');
    Route::put('proyectos/{proyecto}', [ProyectoController::class, 'update'])->name('proyectos.update');
    // Borrado suave, y solo para deshacer una creación equivocada: archivar es
    // pasar el estado a `finalizado`.
    Route::delete('proyectos/{proyecto}', [ProyectoController::class, 'destroy'])->name('proyectos.destroy');

    Route::get('proyectos/{proyecto}/miembros', [ProyectoUsuarioController::class, 'index'])->name('proyectos.miembros.index');
    Route::post('proyectos/{proyecto}/miembros', [ProyectoUsuarioController::class, 'store'])->name('proyectos.miembros.store');
    Route::put('proyectos/{proyecto}/miembros/{usuario}', [ProyectoUsuarioController::class, 'update'])->name('proyectos.miembros.update');
    Route::delete('proyectos/{proyecto}/miembros/{usuario}', [ProyectoUsuarioController::class, 'destroy'])->name('proyectos.miembros.destroy');
    Route::post('proyectos/{proyecto}/equipos', [AsignarEquipoAProyectoController::class, 'store'])->name('proyectos.equipos.store');

    /*
     * Lecturas con guarda. Existen para que la autorización tenga superficie
     * que proteger; su interfaz completa llega en las fases 06, 09 y 10.
     */
    Route::get('proyectos/{proyecto}/modulos', [ModuloController::class, 'index'])->name('proyectos.modulos.index');
    Route::post('proyectos/{proyecto}/modulos', [ModuloController::class, 'store'])->name('proyectos.modulos.store');
    // Una sola petición por arrastre: el nivel completo, no un nodo cada vez.
    Route::put('proyectos/{proyecto}/modulos/orden', [ReordenarModulosController::class, 'update'])->name('proyectos.modulos.orden');
    Route::put('modulos/{modulo}', [ModuloController::class, 'update'])->name('modulos.update');
    Route::delete('modulos/{modulo}', [ModuloController::class, 'destroy'])->name('modulos.destroy');

    /*
     * Tareas. El cambio de estado tiene endpoint propio: si `estado` viajara en
     * el formulario general, cualquiera podría saltarse la matriz de
     * transiciones enviándolo entre los demás campos.
     */
    Route::get('proyectos/{proyecto}/tareas', [TareaController::class, 'index'])->name('proyectos.tareas.index');
    Route::get('proyectos/{proyecto}/tareas/crear', [TareaController::class, 'create'])->name('proyectos.tareas.create');
    Route::post('proyectos/{proyecto}/tareas', [TareaController::class, 'store'])->name('proyectos.tareas.store');

    // Captura rápida: previsualizar no escribe nada; store recibe lo revisado.
    Route::get('proyectos/{proyecto}/tareas/rapida', [CapturaRapidaController::class, 'create'])->name('proyectos.tareas.rapida.create');
    Route::post('proyectos/{proyecto}/tareas/rapida/previsualizar', [CapturaRapidaController::class, 'previsualizar'])->name('proyectos.tareas.rapida.previsualizar');
    Route::post('proyectos/{proyecto}/tareas/rapida', [CapturaRapidaController::class, 'store'])->name('proyectos.tareas.rapida.store');

    /*
     * Las otras tres formas de ver las mismas tareas (fase 08).
     *
     * El tablero tiene endpoint propio y no un parámetro sobre el listado: un
     * paginador plano y un conjunto de columnas con su total son formas de
     * datos distintas, y forzarlas en la misma respuesta las hace peores a las
     * dos. `tablero/columna` sirve el «cargar más» de **una** columna en JSON:
     * una recarga parcial de Inertia rearmaría el tablero entero.
     */
    Route::get('proyectos/{proyecto}/tablero', [TableroController::class, 'index'])->name('proyectos.tablero.index');
    Route::get('proyectos/{proyecto}/tablero/columna', [TableroController::class, 'columna'])->name('proyectos.tablero.columna');
    Route::get('proyectos/{proyecto}/agenda', [AgendaDeTareasController::class, 'index'])->name('proyectos.agenda.index');
    Route::get('proyectos/{proyecto}/lista', [ListaAgrupadaController::class, 'index'])->name('proyectos.lista.index');

    // Reordenar dentro de una columna: una petición por operación con el orden
    // del conjunto afectado, nunca una por tarjeta movida.
    Route::put('proyectos/{proyecto}/tareas/orden', [ReordenarTareasController::class, 'update'])->name('proyectos.tareas.orden');

    /*
     * Mover entre columnas **cuando la columna no es un estado**. Agrupado por
     * estado el camino es otro (`tareas.estado.update`), y esa separación es
     * deliberada: soltar una tarjeta en «Terminada» valida once transiciones,
     * exige motivo y escribe historial; soltarla en otro módulo no hace nada de
     * eso. Un endpoint único con un `if` dentro es el `if` que alguien
     * simplifica el día que no sepa por qué está.
     */
    Route::put('proyectos/{proyecto}/tareas/{tarea}/columna', [MoverTareaEnTableroController::class, 'update'])->name('proyectos.tareas.columna');

    Route::get('proyectos/{proyecto}/periodos', [PeriodoController::class, 'index'])->name('proyectos.periodos.index');
    Route::get('proyectos/{proyecto}/pagos', [PagoController::class, 'index'])->name('proyectos.pagos.index');
    Route::get('proyectos/{proyecto}/reuniones', [ReunionController::class, 'index'])->name('proyectos.reuniones.index');
    Route::post('proyectos/{proyecto}/reportes', [ReporteController::class, 'store'])->name('proyectos.reportes.store');

    /*
     * Listado global de tareas. Va **antes** que `tareas/{tarea}`: con el
     * orden al revés, `/tareas` no llegaría aquí nunca.
     *
     * Lo acota `Tarea::visiblesPara()`, no una policy: `viewAny` pide un
     * proyecto y aquí no hay uno solo.
     */
    Route::get('tareas', ListadoDeTareasController::class)->name('tareas.index');

    Route::get('tareas/{tarea}', [TareaController::class, 'show'])->name('tareas.show');
    Route::get('tareas/{tarea}/editar', [TareaController::class, 'edit'])->name('tareas.edit');
    Route::put('tareas/{tarea}', [TareaController::class, 'update'])->name('tareas.update');
    Route::delete('tareas/{tarea}', [TareaController::class, 'destroy'])->name('tareas.destroy');
    // Autoriza con `cambiarEstado`, no con `update`: el Kanban de la fase 08
    // mueve tarjetas sin editar el resto de campos.
    Route::put('tareas/{tarea}/estado', [EstadoTareaController::class, 'update'])->name('tareas.estado.update');

    Route::get('tareas/{tarea}/notas', [NotaTareaController::class, 'index'])->name('tareas.notas.index');
    Route::post('tareas/{tarea}/notas', [NotaTareaController::class, 'store'])->name('tareas.notas.store');
    Route::get('pagos/{pago}', [PagoController::class, 'show'])->name('pagos.show');
    Route::get('reuniones/{reunion}', [ReunionController::class, 'show'])->name('reuniones.show');

    // Único camino a un adjunto. Nunca una URL directa al disco.
    Route::get('archivos/{media}', [ArchivoController::class, 'show'])->name('archivos.descargar');

    /*
     * Vistas guardadas. Son **privadas de cada usuario**: no llevan `{usuario}`
     * en la ruta y la policy comprueba la propiedad de la fila. Compartirlas
     * suena bien y no lo es —dos personas con la misma vista acaban discutiendo
     * sobre qué debería mostrar—; el día que haga falta, se añade un campo.
     */
    Route::post('vistas', [VistaGuardadaController::class, 'store'])->name('vistas.store');
    Route::put('vistas/{vista}', [VistaGuardadaController::class, 'update'])->name('vistas.update');
    Route::post('vistas/{vista}/duplicar', [VistaGuardadaController::class, 'duplicar'])->name('vistas.duplicar');
    Route::delete('vistas/{vista}', [VistaGuardadaController::class, 'destroy'])->name('vistas.destroy');
});

require __DIR__.'/settings.php';
