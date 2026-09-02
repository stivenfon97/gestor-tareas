<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EstadoPeriodo;
use App\Enums\EstadoTarea;
use App\Enums\OrigenTasaCambio;
use App\Enums\PrioridadTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\TipoReunion;
use App\Enums\Visibilidad;
use App\Models\AsistenteReunion;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\NotaTarea;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Models\User;
use App\Services\CambiarEstadoTarea;
use Database\Factories\ReunionFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos de desarrollo que se parecen a los reales.
 *
 * Es lo que se va a mirar durante las diez fases siguientes: un `lorem ipsum`
 * oculta los problemas de interfaz que un título largo y concreto revela.
 *
 * El seeder es **idempotente**: cada bloque se ancla a una clave estable
 * (correo, slug, consecutivo) y las tareas solo se generan si el proyecto aún
 * no tiene ninguna.
 */
class DesarrolloSeeder extends Seeder
{
    /** @var array<int, array{nombre: string, prefijo: string, cliente: string, modulos: array<string, array<int, string>>}> */
    protected const PROYECTOS = [
        [
            'nombre' => 'Portal de Clientes',
            'prefijo' => 'POR',
            'cliente' => 'Distribuidora El Roble S.A.S.',
            'modulos' => [
                'Clientes' => ['Ficha del cliente', 'Contactos', 'Historial de compras'],
                'Facturación' => ['Emisión', 'Notas crédito'],
                'Reportes' => ['Ventas por mes'],
            ],
        ],
        [
            'nombre' => 'Gestor de Inventario',
            'prefijo' => 'INV',
            'cliente' => 'Distribuidora El Roble S.A.S.',
            'modulos' => [
                'Productos' => ['Catálogo', 'Categorías'],
                'Bodegas' => ['Traslados', 'Conteos físicos'],
                'Alertas' => ['Stock mínimo'],
            ],
        ],
        [
            'nombre' => 'Historia Clínica Digital',
            'prefijo' => 'HCD',
            'cliente' => 'Clínica Santa Marta S.A.',
            'modulos' => [
                'Pacientes' => ['Admisión', 'Antecedentes'],
                'Consultas' => ['Agenda', 'Evolución'],
                'Facturación' => ['Convenios EPS'],
            ],
        ],
        [
            'nombre' => 'Control de Flota',
            'prefijo' => 'FLO',
            'cliente' => 'Transportes Guaviare Ltda.',
            'modulos' => [
                'Vehículos' => ['Ficha técnica', 'Mantenimientos'],
                'Rutas' => ['Programación', 'Seguimiento'],
                'Combustible' => ['Tanqueos'],
            ],
        ],
    ];

    public function run(): void
    {
        $usuarios = $this->crearUsuarios();
        $clientes = $this->crearClientes();

        $this->crearEquipos($usuarios);

        foreach (self::PROYECTOS as $indice => $definicion) {
            $proyecto = $this->crearProyecto($definicion, $clientes[$definicion['cliente']]);

            $this->asignarUsuarios($proyecto, $usuarios);
            $modulos = $this->crearModulos($proyecto, $definicion['modulos']);
            $periodos = $this->crearPeriodos($proyecto, 2 + ($indice % 2));

            if ($proyecto->tareas()->withTrashed()->exists()) {
                $this->command?->info("Proyecto {$proyecto->prefijo_tarea}: ya tiene tareas, se omite la generación.");

                continue;
            }

            $this->crearTareas($proyecto, $modulos, $periodos, $usuarios);
            $this->crearBitacora($proyecto, $usuarios);
        }
    }

    /**
     * @return array<string, User>
     */
    protected function crearUsuarios(): array
    {
        $definiciones = [
            'admin' => ['Stiven Fonseca', 'admin@gestor.test', RolGlobal::Administrador],
            'lider' => ['Marcela Ríos', 'marcela@gestor.test', RolGlobal::Miembro],
            'colaborador' => ['Andrés Peláez', 'andres@gestor.test', RolGlobal::Miembro],
            'cliente' => ['Luz Elena Gómez', 'luzelena@elroble.co', RolGlobal::Invitado],
        ];

        $usuarios = [];

        foreach ($definiciones as $clave => [$nombre, $correo, $rolGlobal]) {
            $usuario = User::query()->firstOrCreate(
                ['email' => $correo],
                [
                    'name' => $nombre,
                    'password' => Hash::make('password'),
                    'email_verified_at' => Carbon::now(),
                    'activo' => true,
                ],
            );

            // syncRoles y no assignRole: el rol global es uno solo, y volver a
            // sembrar no debe dejar a nadie con dos.
            $usuario->syncRoles([$rolGlobal->value]);

            $usuarios[$clave] = $usuario;
        }

        return $usuarios;
    }

    /**
     * @param  array<string, User>  $usuarios
     */
    protected function crearEquipos(array $usuarios): void
    {
        $desarrollo = Equipo::query()->firstOrCreate(
            ['nombre' => 'Desarrollo'],
            ['descripcion' => 'Quienes construyen y corrigen el software.'],
        );

        $desarrollo->usuarios()->syncWithoutDetaching(
            collect($usuarios)->pluck('id')->all(),
        );
    }

    /**
     * @return array<string, Cliente>
     */
    protected function crearClientes(): array
    {
        $definiciones = [
            'Distribuidora El Roble S.A.S.' => ['Luz Elena Gómez', 'compras@elroble.co', '+57 315 442 8890'],
            'Clínica Santa Marta S.A.' => ['Jorge Iván Restrepo', 'sistemas@santamarta.co', '+57 301 778 2145'],
            'Transportes Guaviare Ltda.' => ['Nubia Cárdenas', 'gerencia@tguaviare.co', '+57 320 664 1073'],
        ];

        $clientes = [];

        foreach ($definiciones as $nombre => [$contacto, $correo, $telefono]) {
            $clientes[$nombre] = Cliente::query()->firstOrCreate(
                ['nombre' => $nombre],
                [
                    'contacto_nombre' => $contacto,
                    'contacto_email' => $correo,
                    'contacto_telefono' => $telefono,
                    'activo' => true,
                ],
            );
        }

        return $clientes;
    }

    /**
     * @param  array{nombre: string, prefijo: string, cliente: string, modulos: array<string, array<int, string>>}  $definicion
     */
    protected function crearProyecto(array $definicion, Cliente $cliente): Proyecto
    {
        return Proyecto::query()->firstOrCreate(
            ['slug' => Str::slug($definicion['nombre'])],
            [
                'cliente_id' => $cliente->id,
                'nombre' => $definicion['nombre'],
                'prefijo_tarea' => $definicion['prefijo'],
                'descripcion' => 'Sistema a la medida para '.$cliente->nombre.'.',
            ],
        );
    }

    /**
     * @param  array<string, User>  $usuarios
     */
    protected function asignarUsuarios(Proyecto $proyecto, array $usuarios): void
    {
        $proyecto->usuarios()->syncWithoutDetaching([
            $usuarios['admin']->id => ['rol' => RolProyecto::Lider->value],
            $usuarios['lider']->id => ['rol' => RolProyecto::Lider->value],
            $usuarios['colaborador']->id => ['rol' => RolProyecto::Colaborador->value],
            $usuarios['cliente']->id => ['rol' => RolProyecto::Cliente->value],
        ]);
    }

    /**
     * @param  array<string, array<int, string>>  $arbol
     * @return array<int, Modulo>
     */
    protected function crearModulos(Proyecto $proyecto, array $arbol): array
    {
        $creados = [];
        $orden = 0;

        foreach ($arbol as $nombrePadre => $hijos) {
            $padre = Modulo::query()->firstOrCreate(
                ['proyecto_id' => $proyecto->id, 'parent_id' => null, 'nombre' => $nombrePadre],
                ['orden' => $orden++],
            );

            $creados[] = $padre;
            $ordenHijo = 0;

            foreach ($hijos as $nombreHijo) {
                $creados[] = Modulo::query()->firstOrCreate(
                    ['proyecto_id' => $proyecto->id, 'parent_id' => $padre->id, 'nombre' => $nombreHijo],
                    ['orden' => $ordenHijo++],
                );
            }
        }

        return $creados;
    }

    /**
     * Periodos cerrados con su pago, más uno abierto al final.
     *
     * @return array<int, Periodo>
     */
    protected function crearPeriodos(Proyecto $proyecto, int $cerrados): array
    {
        $periodos = [];
        $inicio = Carbon::now()->startOfMonth()->subMonths($cerrados + 1);

        for ($consecutivo = 1; $consecutivo <= $cerrados; $consecutivo++) {
            $fin = $inicio->copy()->endOfMonth();

            $periodo = Periodo::query()->firstOrCreate(
                ['proyecto_id' => $proyecto->id, 'consecutivo' => $consecutivo],
                [
                    'fecha_inicio' => $inicio->copy(),
                    'fecha_fin' => $fin,
                    'estado' => EstadoPeriodo::Cerrado,
                    'notas' => 'Periodo cerrado y facturado.',
                ],
            );

            Pago::query()->firstOrCreate(
                ['periodo_id' => $periodo->id],
                [
                    'fecha_pago' => $fin->copy()->addDays(5),
                    'monto_usd' => 1200 + ($consecutivo * 150),
                    'tasa_cambio' => 4021.5500 + ($consecutivo * 37.25),
                    'origen_tasa' => OrigenTasaCambio::Api,
                    'referencia' => sprintf('TRF-%s-%02d', $proyecto->prefijo_tarea, $consecutivo),
                ],
            );

            $periodos[] = $periodo;
            $inicio = $inicio->copy()->addMonth();
        }

        $periodos[] = Periodo::query()->firstOrCreate(
            ['proyecto_id' => $proyecto->id, 'consecutivo' => $cerrados + 1],
            [
                'fecha_inicio' => $inicio->copy(),
                'fecha_fin' => null,
                'estado' => EstadoPeriodo::Abierto,
            ],
        );

        return $periodos;
    }

    /**
     * @param  array<int, Modulo>  $modulos
     * @param  array<int, Periodo>  $periodos
     * @param  array<string, User>  $usuarios
     */
    protected function crearTareas(Proyecto $proyecto, array $modulos, array $periodos, array $usuarios): void
    {
        $abierto = $periodos[count($periodos) - 1];
        $cerrados = array_slice($periodos, 0, -1);
        $entregadas = [];

        // La mayoría del trabajo está entregado en periodos viejos.
        foreach ($cerrados as $periodo) {
            for ($i = 0; $i < 14; $i++) {
                $tarea = Tarea::factory()
                    ->for($proyecto)
                    ->create([
                        'modulo_id' => $this->moduloAlAzar($modulos),
                        'periodo_id' => $periodo->id,
                        'creado_por' => $usuarios['admin']->id,
                    ]);

                $this->llevarA($tarea, EstadoTarea::Entregada);
                $entregadas[] = $tarea;
            }

            // Una cancelada por periodo, con su justificación.
            $descartada = Tarea::factory()
                ->for($proyecto)
                ->create([
                    'modulo_id' => $this->moduloAlAzar($modulos),
                    'periodo_id' => $periodo->id,
                    'creado_por' => $usuarios['lider']->id,
                ]);

            $this->llevarA(
                $descartada,
                EstadoTarea::Cancelada,
                'El cliente desistió del módulo en el comité del '.$periodo->fecha_fin?->format('d/m/Y').'.',
            );
        }

        // El periodo abierto: trabajo vivo repartido por los estados no terminales.
        $reparto = [
            [EstadoTarea::Pendiente, 6],
            [EstadoTarea::EnProceso, 4],
            [EstadoTarea::EnEspera, 3],
            [EstadoTarea::Terminada, 3],
        ];

        foreach ($reparto as [$estado, $cantidad]) {
            for ($i = 0; $i < $cantidad; $i++) {
                $tarea = Tarea::factory()
                    ->for($proyecto)
                    ->create([
                        'modulo_id' => $this->moduloAlAzar($modulos),
                        'periodo_id' => $abierto->id,
                        'creado_por' => $usuarios['colaborador']->id,
                    ]);

                $this->llevarA($tarea, $estado);
            }
        }

        // Arrastradas: no se terminaron en el periodo anterior y pasaron a este.
        $origen = $cerrados[count($cerrados) - 1];

        for ($i = 0; $i < 4; $i++) {
            $tarea = Tarea::factory()
                ->for($proyecto)
                ->create([
                    'modulo_id' => $this->moduloAlAzar($modulos),
                    'periodo_id' => $abierto->id,
                    'periodo_origen_id' => $origen->id,
                    'creado_por' => $usuarios['admin']->id,
                ]);

            $this->llevarA($tarea, $i % 2 === 0 ? EstadoTarea::EnProceso : EstadoTarea::Pendiente);
        }

        // Correcciones nacidas de tareas ya entregadas: un fallo posterior no
        // reabre la tarea entregada, crea una nueva de tipo `correccion`.
        foreach (array_slice($entregadas, 0, 5) as $original) {
            $correccion = Tarea::factory()
                ->deCorreccion()
                ->for($proyecto)
                ->create([
                    'modulo_id' => $original->modulo_id,
                    'periodo_id' => $abierto->id,
                    'creado_por' => $usuarios['lider']->id,
                    'descripcion' => "Reportado tras la entrega de {$original->codigo} «{$original->nombre}». "
                        .'El comportamiento se reproduce en producción con datos reales.',
                    'prioridad' => PrioridadTarea::Alta,
                ]);

            $this->llevarA($correccion, EstadoTarea::EnProceso);
        }

        // Algo de soporte, que es tipo y no estado.
        Tarea::factory()
            ->deSoporte()
            ->count(3)
            ->for($proyecto)
            ->create([
                'modulo_id' => $this->moduloAlAzar($modulos),
                'periodo_id' => $abierto->id,
                'creado_por' => $usuarios['colaborador']->id,
            ]);

        $this->crearNotas($proyecto, $usuarios);
    }

    /**
     * @param  array<string, User>  $usuarios
     */
    protected function crearNotas(Proyecto $proyecto, array $usuarios): void
    {
        $tareas = $proyecto->tareas()->inRandomOrder()->limit(12)->get();

        foreach ($tareas as $indice => $tarea) {
            NotaTarea::factory()
                ->for($tarea)
                ->create([
                    'usuario_id' => $usuarios['colaborador']->id,
                    // Solo unas pocas se comparten con el cliente.
                    'visibilidad' => $indice % 4 === 0 ? Visibilidad::Cliente : Visibilidad::Interna,
                ]);
        }
    }

    /**
     * @param  array<string, User>  $usuarios
     */
    protected function crearBitacora(Proyecto $proyecto, array $usuarios): void
    {
        $tareas = $proyecto->tareas()->inRandomOrder()->limit(6)->get();

        $reuniones = [
            ['Definición del alcance del periodo', TipoReunion::Reunion, Visibilidad::Cliente],
            ['Seguimiento de incidencias en producción', TipoReunion::Llamada, Visibilidad::Interna],
            ['Acuerdo sobre el formato de los reportes', TipoReunion::Correo, Visibilidad::Cliente],
        ];

        foreach ($reuniones as $indice => [$titulo, $tipo, $visibilidad]) {
            $parrafos = [
                "Se revisó el avance de {$proyecto->nombre} y se acordó el orden de trabajo del periodo.",
                'Quedaron por confirmar los campos obligatorios del documento y la fecha de la siguiente revisión.',
            ];

            $reunion = Reunion::query()->create([
                'proyecto_id' => $proyecto->id,
                'fecha' => Carbon::now()->subWeeks(($indice + 1) * 2),
                'tipo' => $tipo,
                'titulo' => $titulo,
                'contenido_json' => ReunionFactory::documentoTipTap($parrafos),
                'contenido_html' => '<p>'.implode('</p><p>', $parrafos).'</p>',
                'acuerdos' => 'Priorizar las correcciones abiertas antes de cerrar el periodo.',
                'visibilidad' => $visibilidad,
                'creado_por' => $usuarios['admin']->id,
            ]);

            AsistenteReunion::query()->create([
                'reunion_id' => $reunion->id,
                'usuario_id' => $usuarios['admin']->id,
                'nombre' => $usuarios['admin']->name,
                'organizacion' => 'Equipo de desarrollo',
            ]);

            AsistenteReunion::query()->create([
                'reunion_id' => $reunion->id,
                'usuario_id' => null,
                'nombre' => $proyecto->cliente->contacto_nombre ?? 'Contacto del cliente',
                'organizacion' => $proyecto->cliente->nombre,
            ]);

            $reunion->tareas()->syncWithoutDetaching(
                $tareas->slice($indice * 2, 2)->pluck('id')->all(),
            );
        }
    }

    /**
     * @param  array<int, Modulo>  $modulos
     */
    protected function moduloAlAzar(array $modulos): ?int
    {
        return $modulos[array_rand($modulos)]->id;
    }

    /**
     * Lleva la tarea hasta el estado destino recorriendo la matriz paso a paso.
     *
     * Se hace así, y no fijando el estado final de golpe, para que el historial
     * quede poblado como en la vida real y para que el seeder rompa si alguien
     * cambia la matriz sin darse cuenta.
     *
     * **Pasa por `CambiarEstadoTarea`, igual que la aplicación.** Un
     * `$tarea->update(['estado' => …])` directo aquí validaría la matriz y
     * escribiría el historial —el observer cubre esa red—, pero nunca rellenaría
     * `fecha_inicio` ni `fecha_fin`: esas dos las pone el servicio, no el
     * observer. Así es como las 73 tareas sembradas llegaron a "Sin fecha de
     * fin" en el calendario aunque 42 de ellas estuvieran `entregada` (D9.2).
     */
    protected function llevarA(Tarea $tarea, EstadoTarea $destino, ?string $motivo = null): void
    {
        $camino = match ($destino) {
            EstadoTarea::Pendiente => [],
            EstadoTarea::EnProceso => [EstadoTarea::EnProceso],
            EstadoTarea::EnEspera => [EstadoTarea::EnProceso, EstadoTarea::EnEspera],
            EstadoTarea::Terminada => [EstadoTarea::EnProceso, EstadoTarea::Terminada],
            EstadoTarea::Entregada => [EstadoTarea::EnProceso, EstadoTarea::Terminada, EstadoTarea::Entregada],
            EstadoTarea::Cancelada => [EstadoTarea::Cancelada],
        };

        $cambiar = app(CambiarEstadoTarea::class);

        foreach ($camino as $paso) {
            $motivos = [];

            if ($paso === EstadoTarea::EnEspera) {
                $motivos['motivo_espera'] = $motivo
                    ?? 'El cliente no ha entregado la información necesaria para continuar.';
            }

            if ($paso === EstadoTarea::Cancelada) {
                $motivos['motivo_cancelacion'] = $motivo
                    ?? 'Se descartó de común acuerdo con el cliente.';
            }

            $cambiar($tarea, $paso, $motivos);
        }
    }
}
