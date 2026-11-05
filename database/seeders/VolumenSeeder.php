<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EstadoProyecto;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos de volumen: lo que se mira para saber cómo se comporta la interfaz con
 * decenas de clientes, cientos de usuarios y miles de tareas.
 *
 * **Es opcional y va aparte de `DatabaseSeeder`**: siembra unas cuatro mil
 * tareas pasando cada cambio de estado por `CambiarEstadoTarea`, y eso tarda
 * minutos. Se corre a mano:
 *
 *     php artisan db:seed --class=VolumenSeeder
 *
 * Hereda de `DesarrolloSeeder` para reutilizar sus caminos de escritura
 * —periodos con su pago, árbol de módulos, tareas llevadas a su estado por el
 * servicio y bitácora— en lugar de copiarlos. Los datos base de desarrollo se
 * siembran primero, así que la cuenta de administrador de pruebas existe.
 *
 * **Idempotente**: todo lo que crea lleva el dominio `volumen.test` en el
 * correo, y si ya hay alguno, no vuelve a sembrar.
 */
class VolumenSeeder extends DesarrolloSeeder
{
    public const DOMINIO = 'volumen.test';

    protected int $totalUsuarios = 72;

    protected int $totalClientes = 48;

    /** Proyectos por cliente: entre cero y este número. */
    protected int $proyectosPorCliente = 2;

    /** Periodos cerrados por proyecto: entre uno y este número. */
    protected int $periodosCerradosMaximos = 3;

    private const EQUIPOS = [
        'Backend' => 'Servicios, API e integraciones del lado del servidor.',
        'Frontend' => 'Pantallas, formularios y accesibilidad.',
        'Calidad' => 'Pruebas funcionales y de regresión antes de cada entrega.',
        'Infraestructura' => 'Servidores, despliegues y respaldos.',
        'Soporte de primer nivel' => 'Atiende los reportes de los clientes y los clasifica.',
        'Soporte de segundo nivel' => 'Resuelve lo que el primer nivel no puede cerrar.',
        'Diseño de experiencia' => 'Investigación con usuarios y prototipos.',
        'Datos y reportes' => 'Tableros, exportaciones y consultas a la medida.',
        'Aplicaciones móviles' => 'Apps nativas e híbridas.',
        'Seguridad' => 'Revisiones de acceso, auditorías y parches.',
        'Arquitectura' => 'Decisiones técnicas transversales a los proyectos.',
        'Gestión de proyectos' => 'Planeación de periodos y relación con el cliente.',
    ];

    private const RAZONES = [
        'Distribuidora', 'Inversiones', 'Clínica', 'Transportes', 'Textiles', 'Agrícola',
        'Constructora', 'Laboratorios', 'Ferretería', 'Colegio', 'Cooperativa', 'Hotel',
        'Comercializadora', 'Farmacia', 'Industrias', 'Logística',
    ];

    private const NOMBRES_COMERCIALES = [
        'Andina', 'del Pacífico', 'La Esperanza', 'Santa Lucía', 'El Dorado', 'Los Cerros',
        'Nueva Granada', 'Monteverde', 'Río Claro', 'San Rafael', 'La Cumbre', 'Altamira',
    ];

    private const SISTEMAS = [
        'Portal de Proveedores', 'Facturación Electrónica', 'App de Domicilios',
        'Tablero de Indicadores', 'Nómina en Línea', 'Agenda de Citas', 'Control de Acceso',
        'CRM Comercial', 'Gestión Documental', 'Tienda Virtual', 'Inventario de Bodega',
        'Mesa de Ayuda', 'Liquidación de Comisiones', 'Seguimiento de Pedidos',
        'Intranet Corporativa', 'Encuestas de Satisfacción', 'Matrículas en Línea',
        'Reservas de Habitaciones', 'Trazabilidad de Lotes', 'Cartera y Recaudo',
    ];

    /** @var array<string, array<int, string>> */
    private const ARBOL_DE_MODULOS = [
        'Administración' => ['Usuarios', 'Parámetros', 'Auditoría'],
        'Operación' => ['Registro', 'Aprobaciones', 'Seguimiento'],
        'Finanzas' => ['Facturación', 'Pagos', 'Conciliación'],
        'Reportes' => ['Indicadores', 'Exportaciones'],
        'Integraciones' => ['API pública', 'Webhooks'],
    ];

    public function run(): void
    {
        parent::run();

        if (User::query()->where('email', 'like', '%@'.self::DOMINIO)->exists()) {
            $this->command?->info('Los datos de volumen ya estaban sembrados, se omite.');

            return;
        }

        $usuarios = $this->crearUsuariosDeVolumen();
        $this->crearEquiposDeVolumen($usuarios);

        $clientes = $this->crearClientesDeVolumen();
        $this->crearProyectosDeVolumen($clientes, $usuarios);
    }

    /**
     * Administradores, miembros e invitados, con una parte desactivada.
     *
     * @return Collection<int, User>
     */
    protected function crearUsuariosDeVolumen(): Collection
    {
        $clave = Hash::make('password');

        return collect(range(1, $this->totalUsuarios))->map(function (int $numero) use ($clave): User {
            $nombre = fake()->firstName().' '.fake()->lastName().' '.fake()->lastName();
            $rol = match (true) {
                $numero % 12 === 0 => RolGlobal::Administrador,
                $numero % 4 === 3 => RolGlobal::Invitado,
                default => RolGlobal::Miembro,
            };

            $usuario = User::query()->create([
                'name' => $nombre,
                'email' => Str::slug($nombre, '.').'.'.$numero.'@'.self::DOMINIO,
                'password' => $clave,
                'activo' => $rol === RolGlobal::Administrador || $numero % 9 !== 0,
            ]);

            $usuario->forceFill(['email_verified_at' => Carbon::now()])->save();
            $usuario->syncRoles([$rol->value]);

            return $usuario;
        });
    }

    /**
     * @param  Collection<int, User>  $usuarios
     */
    protected function crearEquiposDeVolumen(Collection $usuarios): void
    {
        $internos = $usuarios->filter(fn (User $usuario): bool => $usuario->activo && $usuario->rolGlobal() !== RolGlobal::Invitado);

        foreach (self::EQUIPOS as $nombre => $descripcion) {
            $equipo = Equipo::query()->firstOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);

            $equipo->usuarios()->syncWithoutDetaching(
                $internos->random(min($internos->count(), fake()->numberBetween(3, 12)))->pluck('id')->all(),
            );
        }
    }

    /**
     * @return Collection<int, Cliente>
     */
    protected function crearClientesDeVolumen(): Collection
    {
        $nombres = collect(self::RAZONES)
            ->crossJoin(self::NOMBRES_COMERCIALES)
            ->shuffle()
            ->take($this->totalClientes)
            ->values();

        return $nombres->map(function (array $partes, int $indice): Cliente {
            [$razon, $comercial] = $partes;
            $base = $razon.' '.$comercial;

            return Cliente::query()->create([
                'nombre' => $base.' '.fake()->randomElement(['S.A.S.', 'Ltda.', 'S.A.']),
                'contacto_nombre' => fake()->name(),
                'contacto_email' => 'contacto.'.($indice + 1).'@'.self::DOMINIO,
                'contacto_telefono' => '+57 3'.fake()->numerify('## ### ####'),
                'notas' => fake()->optional(0.4)->sentence(12),
                'activo' => $indice % 6 !== 5,
            ]);
        });
    }

    /**
     * Proyectos con su equipo, módulos, periodos pagados, tareas y bitácora.
     *
     * Los administradores globales entran como líderes de todos: sin eso el
     * listado de tareas, que solo enseña los proyectos donde se participa,
     * saldría casi vacío al entrar a mirar el volumen.
     *
     * @param  Collection<int, Cliente>  $clientes
     * @param  Collection<int, User>  $usuarios
     */
    protected function crearProyectosDeVolumen(Collection $clientes, Collection $usuarios): void
    {
        $activos = $usuarios->filter(fn (User $usuario): bool => $usuario->activo);
        $miembros = $activos->filter(fn (User $usuario): bool => $usuario->rolGlobal() === RolGlobal::Miembro)->values();
        $invitados = $activos->filter(fn (User $usuario): bool => $usuario->rolGlobal() === RolGlobal::Invitado)->values();
        $administradores = User::role(RolGlobal::Administrador->value)->where('activo', true)->get();

        $secuencia = 0;

        foreach ($clientes as $cliente) {
            $cantidad = fake()->numberBetween($cliente->activo ? 1 : 0, $this->proyectosPorCliente);

            foreach (fake()->randomElements(self::SISTEMAS, $cantidad) as $sistema) {
                $secuencia++;
                $proyecto = $this->crearProyectoDeVolumen($cliente, $sistema, $secuencia);

                $lider = $miembros->random();
                $colaboradores = $miembros->reject(fn (User $usuario): bool => $usuario->is($lider))
                    ->random(fake()->numberBetween(2, 5));
                $contacto = $invitados->random();

                $proyecto->usuarios()->syncWithoutDetaching(
                    $administradores->mapWithKeys(fn (User $usuario): array => [$usuario->id => ['rol' => RolProyecto::Lider->value]])->all()
                    + [$lider->id => ['rol' => RolProyecto::Lider->value]]
                    + $colaboradores->mapWithKeys(fn (User $usuario): array => [$usuario->id => ['rol' => RolProyecto::Colaborador->value]])->all()
                    + [$contacto->id => ['rol' => RolProyecto::Cliente->value]],
                );

                $modulos = $this->crearModulos($proyecto, self::ARBOL_DE_MODULOS);
                $periodos = $this->crearPeriodos($proyecto, fake()->numberBetween(1, $this->periodosCerradosMaximos));
                $this->variarPagos($periodos);

                $equipo = [
                    'admin' => $lider,
                    'lider' => $lider,
                    'colaborador' => $colaboradores->first(),
                    'cliente' => $contacto,
                ];

                $this->crearTareas($proyecto, $modulos, $periodos, $equipo);
                $this->crearBitacora($proyecto, $equipo);
            }
        }
    }

    protected function crearProyectoDeVolumen(Cliente $cliente, string $sistema, int $secuencia): Proyecto
    {
        $nombre = $sistema.' · '.Str::of($cliente->nombre)->before(' S.')->before(' Ltda.')->toString();
        $iniciales = Str::of($sistema)->explode(' ')
            ->filter(fn (string $palabra): bool => mb_strlen($palabra) > 2)
            ->map(fn (string $palabra): string => mb_strtoupper(Str::ascii(mb_substr($palabra, 0, 1))))
            ->take(3)
            ->implode('');

        return Proyecto::query()->create([
            'cliente_id' => $cliente->id,
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.$secuencia,
            'prefijo_tarea' => $iniciales.str_pad((string) $secuencia, 2, '0', STR_PAD_LEFT),
            'descripcion' => 'Sistema de '.mb_strtolower($sistema).' para '.$cliente->nombre.'.',
            'estado' => fake()->randomElement([
                EstadoProyecto::Activo, EstadoProyecto::Activo, EstadoProyecto::Activo,
                EstadoProyecto::Activo, EstadoProyecto::Pausado, EstadoProyecto::Finalizado,
            ]),
        ]);
    }

    /**
     * `crearPeriodos()` paga siempre lo mismo; en volumen interesa ver montos
     * distintos en los listados. Pasa por el modelo para que el observer
     * recalcule `monto_cop`.
     *
     * @param  array<int, Periodo>  $periodos
     */
    protected function variarPagos(array $periodos): void
    {
        foreach ($periodos as $periodo) {
            $periodo->pago()->first()?->update([
                'monto_usd' => fake()->randomFloat(2, 850, 5200),
                'tasa_cambio' => fake()->randomFloat(4, 3850, 4450),
            ]);
        }
    }
}
