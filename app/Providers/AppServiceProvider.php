<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\NotaTarea;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reporte;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Models\User;
use App\Models\VistaGuardada;
use App\Policies\ClientePolicy;
use App\Policies\EquipoPolicy;
use App\Policies\MediaPolicy;
use App\Policies\ModuloPolicy;
use App\Policies\NotaTareaPolicy;
use App\Policies\PagoPolicy;
use App\Policies\PeriodoPolicy;
use App\Policies\ProyectoPolicy;
use App\Policies\ReportePolicy;
use App\Policies\ReunionPolicy;
use App\Policies\TareaPolicy;
use App\Policies\VistaGuardadaPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registrarPolicies();
    }

    /**
     * Las doce policies se registran explícitamente para que el listado sea
     * legible de un vistazo: si una falta, se ve aquí.
     */
    protected function registrarPolicies(): void
    {
        Gate::policy(Cliente::class, ClientePolicy::class);
        Gate::policy(Proyecto::class, ProyectoPolicy::class);
        Gate::policy(Modulo::class, ModuloPolicy::class);
        Gate::policy(Tarea::class, TareaPolicy::class);
        Gate::policy(Periodo::class, PeriodoPolicy::class);
        Gate::policy(Pago::class, PagoPolicy::class);
        Gate::policy(Reunion::class, ReunionPolicy::class);
        Gate::policy(NotaTarea::class, NotaTareaPolicy::class);
        Gate::policy(Reporte::class, ReportePolicy::class);
        Gate::policy(Equipo::class, EquipoPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(VistaGuardada::class, VistaGuardadaPolicy::class);

        // Único atajo por encima de las policies. Deliberadamente solo para el
        // administrador: cualquier otro `before` convierte las policies en
        // decorado.
        Gate::before(fn (User $usuario) => $usuario->esAdministrador() ? true : null);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
