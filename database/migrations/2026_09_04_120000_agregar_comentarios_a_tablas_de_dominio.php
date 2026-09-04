<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comentarios de documentación, a nivel de motor de base de datos, en las 17
 * tablas de dominio y sus columnas menos obvias.
 *
 * Antes de esta migración el razonamiento de diseño solo vivía en el código
 * de las migraciones (comentarios PHP) y en los `@property` de los modelos:
 * invisible para quien abre la base directamente con un cliente de MySQL.
 * `information_schema` confirmaba 0 `TABLE_COMMENT` y 0 `COLUMN_COMMENT` en
 * las 239 columnas existentes.
 *
 * Solo corre en MySQL/MariaDB. La conexión de test es SQLite en memoria
 * (`phpunit.xml`) y ese motor no soporta comentarios de tabla ni de columna
 * —Laravel simplemente los ignora ahí—, pero sí soporta `->change()`, y ese
 * método reconstruye la tabla completa en SQLite. Dos tablas de este dominio
 * tienen una columna generada (`vistas_guardadas.marca_predeterminada`,
 * `periodos.proyecto_si_abierto`) con un índice único encima: arriesgar esa
 * reconstrucción por un comentario que SQLite ni siquiera va a guardar no
 * compensa. De ahí el guard, no un descuido.
 *
 * No toca las tablas que impone Laravel o sus paquetes (`users`, `sessions`,
 * `roles`, `media`...). Esas están exceptuadas de la convención de nombres en
 * español a propósito y no son responsabilidad de esta migración.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->clientes();
        $this->proyectos();
        $this->modulos();
        $this->equipos();
        $this->equipoUsuario();
        $this->proyectoUsuario();
        $this->periodos();
        $this->pagos();
        $this->contadoresTarea();
        $this->tareas();
        $this->notasTarea();
        $this->historialEstados();
        $this->reuniones();
        $this->asistentesReunion();
        $this->reunionTarea();
        $this->reportes();
        $this->vistasGuardadas();
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ([
            'clientes', 'proyectos', 'modulos', 'equipos', 'equipo_usuario',
            'proyecto_usuario', 'periodos', 'pagos', 'contadores_tarea', 'tareas',
            'notas_tarea', 'historial_estados', 'reuniones', 'asistentes_reunion',
            'reunion_tarea', 'reportes', 'vistas_guardadas',
        ] as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->comment(''));
        }

        Schema::table('clientes', function (Blueprint $t): void {
            $t->boolean('activo')->default(true)->change();
        });

        Schema::table('proyectos', function (Blueprint $t): void {
            $t->foreignId('cliente_id')->change();
            $t->string('slug')->change();
            $t->string('prefijo_tarea', 10)->change();
            $t->string('estado', 20)->default('activo')->change();
        });

        Schema::table('modulos', function (Blueprint $t): void {
            $t->foreignId('parent_id')->nullable()->change();
            $t->integer('orden')->default(0)->change();
        });

        Schema::table('proyecto_usuario', function (Blueprint $t): void {
            $t->string('rol', 20)->change();
        });

        Schema::table('periodos', function (Blueprint $t): void {
            $t->integer('consecutivo')->change();
            $t->date('fecha_fin')->nullable()->change();
            $t->string('estado', 20)->default('abierto')->change();
        });

        Schema::table('pagos', function (Blueprint $t): void {
            $t->foreignId('periodo_id')->change();
            $t->decimal('monto_usd', 12, 2)->change();
            $t->decimal('tasa_cambio', 12, 4)->change();
            $t->decimal('monto_cop', 14, 2)->change();
            $t->string('origen_tasa', 20)->change();
        });

        Schema::table('contadores_tarea', function (Blueprint $t): void {
            $t->unsignedInteger('ultimo_numero')->default(0)->change();
        });

        Schema::table('tareas', function (Blueprint $t): void {
            $t->foreignId('modulo_id')->nullable()->change();
            $t->foreignId('periodo_id')->nullable()->change();
            $t->foreignId('periodo_origen_id')->nullable()->change();
            $t->foreignId('tarea_origen_id')->nullable()->change();
            $t->string('codigo')->change();
            $t->string('estado', 20)->default('pendiente')->change();
            $t->string('tipo', 20)->change();
            $t->string('prioridad', 20)->change();
            $t->text('motivo_espera')->nullable()->change();
            $t->text('motivo_cancelacion')->nullable()->change();
            $t->date('fecha_inicio')->nullable()->change();
            $t->date('fecha_fin')->nullable()->change();
            $t->foreignId('creado_por')->change();
        });

        Schema::table('notas_tarea', function (Blueprint $t): void {
            $t->foreignId('usuario_id')->change();
            $t->string('visibilidad', 20)->default('interna')->change();
        });

        Schema::table('historial_estados', function (Blueprint $t): void {
            $t->foreignId('usuario_id')->nullable()->change();
            $t->string('estado_anterior', 20)->nullable()->change();
            $t->string('estado_nuevo', 20)->change();
        });

        Schema::table('reuniones', function (Blueprint $t): void {
            $t->json('contenido_json')->change();
            $t->longText('contenido_html')->change();
            $t->string('visibilidad', 20)->default('interna')->change();
            $t->foreignId('creado_por')->change();
        });

        Schema::table('asistentes_reunion', function (Blueprint $t): void {
            $t->foreignId('usuario_id')->nullable()->change();
            $t->string('nombre')->change();
        });

        Schema::table('reportes', function (Blueprint $t): void {
            $t->string('tipo', 20)->change();
            $t->json('filtros')->change();
            $t->json('resumen_estados')->change();
            $t->json('snapshot')->change();
            $t->string('hash', 40)->change();
            $t->foreignId('generado_por')->change();
        });

        Schema::table('vistas_guardadas', function (Blueprint $t): void {
            $t->foreignId('proyecto_id')->nullable()->change();
            $t->json('configuracion')->change();
            $t->boolean('es_predeterminada')->default(false)->change();
        });
    }

    private function clientes(): void
    {
        Schema::table('clientes', function (Blueprint $t): void {
            $t->comment('Cliente: empresa o persona que contrata. Tiene uno o más proyectos.');

            $t->boolean('activo')->default(true)
                ->comment('Baja lógica: un cliente inactivo no se borra, para no perder el historial de sus proyectos.')
                ->change();
        });
    }

    private function proyectos(): void
    {
        Schema::table('proyectos', function (Blueprint $t): void {
            $t->comment('Proyecto: una aplicación o sistema de un cliente. Tiene módulos, tareas y periodos propios.');

            $t->foreignId('cliente_id')
                ->comment('FK restrict: borrar un cliente con proyectos vivos es un accidente, no una operación de negocio.')
                ->change();

            $t->string('slug')
                ->comment('Identificador único y legible en URL, generado desde el nombre por Proyecto::slugUnico().')
                ->change();

            $t->string('prefijo_tarea', 10)
                ->comment('Prefijo del código correlativo de tarea (ej. MAQ-0001). Se normaliza a mayúsculas y es inmutable en cuanto el proyecto emite su primer código.')
                ->change();

            $t->string('estado', 20)->default('activo')
                ->comment('Ciclo de vida del proyecto (ver enum EstadoProyecto). No confundir con el estado de una tarea: son ejes distintos.')
                ->change();
        });
    }

    private function modulos(): void
    {
        Schema::table('modulos', function (Blueprint $t): void {
            $t->comment('Módulo: división funcional de un proyecto. Autoreferencial: puede contener submódulos vía parent_id.');

            $t->foreignId('parent_id')->nullable()
                ->comment('Módulo padre. NULL en los módulos de primer nivel (raíz).')
                ->change();

            $t->integer('orden')->default(0)
                ->comment('Posición manual dentro de su nivel, para el orden mostrado en la interfaz.')
                ->change();
        });
    }

    private function equipos(): void
    {
        Schema::table('equipos', function (Blueprint $t): void {
            $t->comment('Equipo: agrupación reutilizable de usuarios. Es solo un atajo de asignación masiva a proyectos; el rol efectivo de un usuario en un proyecto vive siempre en el pivote proyecto_usuario, nunca aquí.');
        });
    }

    private function equipoUsuario(): void
    {
        Schema::table('equipo_usuario', function (Blueprint $t): void {
            $t->comment('Pivote: usuarios que pertenecen a un equipo.');
        });
    }

    private function proyectoUsuario(): void
    {
        Schema::table('proyecto_usuario', function (Blueprint $t): void {
            $t->comment('Pivote: usuarios asignados a un proyecto, con su rol específico en ese proyecto. Un usuario tiene un solo rol por proyecto (unique proyecto_id+usuario_id).');

            $t->string('rol', 20)
                ->comment('Rol del usuario en ESTE proyecto (ver enum RolProyecto). Es la única fuente de verdad del rol efectivo, incluso si el usuario llegó vía un equipo.')
                ->change();
        });
    }

    private function periodos(): void
    {
        Schema::table('periodos', function (Blueprint $t): void {
            $t->comment('Periodo: bloque de trabajo facturable de un proyecto. Fecha de inicio, fecha de fin y un pago. Se cierra manualmente.');

            $t->integer('consecutivo')
                ->comment('Número correlativo del periodo dentro de su proyecto (1, 2, 3...). Único junto con proyecto_id.')
                ->change();

            $t->date('fecha_fin')->nullable()
                ->comment('NULL mientras el periodo sigue abierto. Superada y en NULL no es un error: es un estado válido y frecuente.')
                ->change();

            $t->string('estado', 20)->default('abierto')
                ->comment('abierto/cerrado (ver enum EstadoPeriodo). Solo puede haber un periodo abierto a la vez por proyecto, garantizado por un índice único sobre una columna generada.')
                ->change();
        });
    }

    private function pagos(): void
    {
        Schema::table('pagos', function (Blueprint $t): void {
            $t->comment('Pago: el cobro de un periodo. Monto en USD, tasa de cambio (TRM) y monto en COP. Relación 1 a 1 con periodos, garantizada por el unique de periodo_id.');

            $t->foreignId('periodo_id')
                ->comment('Unique: es lo que hace real la regla "un periodo, un pago" a nivel de base de datos, no solo de intención.')
                ->change();

            $t->decimal('monto_usd', 12, 2)
                ->comment('Monto acordado en dólares. Decimal, nunca float: la coma flotante binaria pierde centavos.')
                ->change();

            $t->decimal('tasa_cambio', 12, 4)
                ->comment('TRM aplicada para convertir monto_usd a monto_cop. Más precisión que el dinero porque es un factor de conversión, no un monto.')
                ->change();

            $t->decimal('monto_cop', 14, 2)
                ->comment('Monto en pesos colombianos, PERSISTIDO en el momento del pago, no calculado en lectura: corregir la TRM después no debe reescribir lo que ya se cobró.')
                ->change();

            $t->string('origen_tasa', 20)
                ->comment('De dónde salió la TRM usada (ver enum OrigenTasaCambio: manual o automática).')
                ->change();
        });
    }

    private function contadoresTarea(): void
    {
        Schema::table('contadores_tarea', function (Blueprint $t): void {
            $t->comment('Contador correlativo de códigos de tarea por proyecto. Evita la condición de carrera de calcular max(codigo)+1: TareaObserver bloquea esta fila con lockForUpdate(). Un borrado de tarea no libera ni reutiliza el número ya emitido.');

            $t->unsignedInteger('ultimo_numero')->default(0)
                ->comment('Último número de tarea entregado en este proyecto. Solo lo escribe TareaObserver.')
                ->change();
        });
    }

    private function tareas(): void
    {
        Schema::table('tareas', function (Blueprint $t): void {
            $t->comment('Tarea: unidad de trabajo. Pertenece a un proyecto, un módulo y un periodo. Estado, tipo y prioridad son tres ejes independientes.');

            $t->foreignId('modulo_id')->nullable()
                ->comment('NULL ON DELETE: borrar un módulo no borra su trabajo, la tarea queda suelta (sin módulo) en vez de desaparecer.')
                ->change();

            $t->foreignId('periodo_id')->nullable()
                ->comment('Periodo facturable actual de la tarea. NULL si aún no se asignó a ninguno.')
                ->change();

            $t->foreignId('periodo_origen_id')->nullable()
                ->comment('Periodo del que se arrastró la tarea por no terminarse a tiempo (tarea arrastrada). Se conserva aunque la tarea avance de periodo.')
                ->change();

            $t->foreignId('tarea_origen_id')->nullable()
                ->comment('Entrega que originó esta corrección/soporte, si la hay. SET NULL y no cascade: si se borra la entrega original, la corrección sigue siendo trabajo hecho y cuenta en su periodo.')
                ->change();

            $t->string('codigo')
                ->comment('Código correlativo único del proyecto (ej. MAQ-0001), tomado de contadores_tarea.')
                ->change();

            $t->string('estado', 20)->default('pendiente')
                ->comment('Punto del ciclo de vida (ver enum EstadoTarea). entregada y cancelada son terminales: no admiten ninguna transición de salida.')
                ->change();

            $t->string('tipo', 20)
                ->comment('Naturaleza del trabajo (ver enum TipoTarea). No cambia con el tiempo, a diferencia de la prioridad.')
                ->change();

            $t->string('prioridad', 20)
                ->comment('Urgencia relativa (ver enum PrioridadTarea). Cambia con el tiempo, a diferencia del tipo.')
                ->change();

            $t->text('motivo_espera')->nullable()
                ->comment('Obligatorio al entrar en en_espera. Campo distinto de motivo_cancelacion: son cosas diferentes, consultadas en momentos diferentes.')
                ->change();

            $t->text('motivo_cancelacion')->nullable()
                ->comment('Obligatorio al entrar en cancelada. No se reutiliza con motivo_espera.')
                ->change();

            $t->date('fecha_inicio')->nullable()
                ->comment('Se fija en la PRIMERA entrada a en_proceso y no se sobrescribe después.')
                ->change();

            $t->date('fecha_fin')->nullable()
                ->comment('Se actualiza cada vez que la tarea reentra en terminada (p. ej. tras una revisión fallida y un segundo intento).')
                ->change();

            $t->foreignId('creado_por')
                ->comment('FK restrict: quién creó la tarea. No se borra el usuario para no romper este historial.')
                ->change();
        });
    }

    private function notasTarea(): void
    {
        Schema::table('notas_tarea', function (Blueprint $t): void {
            $t->comment('Nota: comentario sobre una tarea.');

            $t->foreignId('usuario_id')
                ->comment('FK restrict: autor de la nota. No se borra el usuario para no romper la autoría.')
                ->change();

            $t->string('visibilidad', 20)->default('interna')
                ->comment('interna/cliente (ver enum Visibilidad). Nace SIEMPRE interna: exponerla al cliente es una decisión explícita posterior.')
                ->change();
        });
    }

    private function historialEstados(): void
    {
        Schema::table('historial_estados', function (Blueprint $t): void {
            $t->comment('Bitácora de cambios de estado de una tarea. Solo lo escribe TareaObserver. Es un registro histórico: no tiene updated_at, no se corrige, se añade otro.');

            $t->foreignId('usuario_id')->nullable()
                ->comment('NULL ON DELETE: si el usuario desaparece, el hecho registrado sigue siendo cierto.')
                ->change();

            $t->string('estado_anterior', 20)->nullable()
                ->comment('NULL en el primer registro de la tarea (su creación).')
                ->change();

            $t->string('estado_nuevo', 20)
                ->comment('Estado al que quedó la tarea tras este cambio.')
                ->change();
        });
    }

    private function reuniones(): void
    {
        Schema::table('reuniones', function (Blueprint $t): void {
            $t->comment('Reunión: un encuentro, llamada o intercambio de la bitácora de un proyecto, con notas enriquecidas y adjuntos.');

            $t->json('contenido_json')
                ->comment('Documento del editor TipTap: es la fuente editable.')
                ->change();

            $t->longText('contenido_html')
                ->comment('Copia renderizada de contenido_json, saneada en el servidor antes de guardar. Se pinta con v-html: un fallo de saneado es ejecución de código en el navegador de quien la abra.')
                ->change();

            $t->string('visibilidad', 20)->default('interna')
                ->comment('interna/cliente (ver enum Visibilidad). Nace interna.')
                ->change();

            $t->foreignId('creado_por')
                ->comment('FK restrict: quién registró la reunión.')
                ->change();
        });
    }

    private function asistentesReunion(): void
    {
        Schema::table('asistentes_reunion', function (Blueprint $t): void {
            $t->comment('Quién estuvo en una reunión, sea o no usuario del sistema.');

            $t->foreignId('usuario_id')->nullable()
                ->comment('NULL ON DELETE. Opcional: la gente del cliente no es usuaria del sistema.')
                ->change();

            $t->string('nombre')
                ->comment('Obligatorio incluso con usuario_id: debe sobrevivir al borrado del usuario.')
                ->change();
        });
    }

    private function reunionTarea(): void
    {
        Schema::table('reunion_tarea', function (Blueprint $t): void {
            $t->comment('Pivote: tareas vinculadas a una reunión.');
        });
    }

    private function reportes(): void
    {
        Schema::table('reportes', function (Blueprint $t): void {
            $t->comment('Reporte: archivo Excel generado para un periodo, con registro permanente de su emisión. Los .xlsx no se persisten en disco, se generan bajo demanda; por eso no hay columna de ruta de archivo.');

            $t->string('tipo', 20)
                ->comment('completo/cliente (ver enum TipoReporte): dos archivos distintos, con columnas distintas.')
                ->change();

            $t->json('filtros')
                ->comment('Filtros aplicados al generar el reporte, para trazabilidad de qué se incluyó.')
                ->change();

            $t->json('resumen_estados')
                ->comment('Conteo de tareas por estado en el momento de generar, para el resumen del archivo.')
                ->change();

            $t->json('snapshot')
                ->comment('Filas exactas exportadas en el momento de generar. Es lo único que permite reconstruir, meses después, el archivo idéntico al que se envió.')
                ->change();

            $t->string('hash', 40)
                ->comment('Huella del contenido exportado, para verificar la integridad del snapshot.')
                ->change();

            $t->foreignId('generado_por')
                ->comment('FK restrict: quién generó el reporte.')
                ->change();
        });
    }

    private function vistasGuardadas(): void
    {
        Schema::table('vistas_guardadas', function (Blueprint $t): void {
            $t->comment('Vista guardada: filtros, agrupación y columnas que un usuario guarda para reutilizar. Es un nombre y una cadena de consulta: el estado de toda vista vive en la URL. Privada de su autor, nunca compartida.');

            $t->foreignId('proyecto_id')->nullable()
                ->comment('NULL significa que la vista es global (aplica a todos los proyectos), no solo a uno.')
                ->change();

            $t->json('configuracion')
                ->comment('Los mismos parámetros que viajan en la URL de la pantalla (filtros, agrupación, columnas).')
                ->change();

            $t->boolean('es_predeterminada')->default(false)
                ->comment('Si se aplica automáticamente al entrar a la pantalla. Solo una por usuario y tipo, garantizado por índice único sobre la columna generada marca_predeterminada.')
                ->change();
        });
    }
};
