# Informe de mantenimiento — post-fase-12

Este documento no describe una fase de desarrollo. El proyecto cerró en la
**fase 12** (`docs/06-historial-de-decisiones.md`, commit `9d33db3`); todo lo de
aquí son ajustes puntuales hechos después, sobre `main` directamente, a petición
explícita del usuario en cada paso — nunca por una rama `fase/NN` con su propio
alcance planeado de antemano.

Sirve como resumen de una sola lectura de **qué cambió, por qué, y cómo se
verificó**. El detalle de cada decisión —con su alternativa descartada— vive en
[`06-historial-de-decisiones.md`](06-historial-de-decisiones.md#mantenimiento-post-fase-12);
este documento es el mapa de conjunto.

## Resumen

| Área | Qué cambió |
|---|---|
| Base de datos | 17 tablas de dominio con comentarios de tabla y columna; collation unificado en `utf8mb4_0900_ai_ci` |
| Zona horaria | `APP_TIMEZONE` conectado de verdad (estaba muerto); corrige un bug real de fechas cerca de medianoche |
| Despliegue | Script `desplegar.sh` nuevo: valida el entorno, levanta todo, migra, crea el primer administrador, informa |
| Autenticación | Roles y permisos sembrados desde una migración (ya no dependen de `--seed`); dos comandos Artisan nuevos |
| Compatibilidad | Bind mount del código con `:z`, requisito real para Fedora/RHEL con SELinux |
| Documentación | 7 documentos actualizados para que reflejen todo lo anterior |

7 commits, 22 archivos, +2055/-15 líneas. Suite completa en verde en todo momento:
**481 passed, 4 skipped, 2175 assertions** al cierre de esta sesión (arrancó en
472 passed antes del primer cambio).

## 1. Comentarios de tabla y columna en la base de datos

**Commit:** `c34dc18` — `docs(bd): comentarios de tabla y columna en las 17 tablas de dominio`

`information_schema` no tenía ni un `TABLE_COMMENT` ni un `COLUMN_COMMENT` en las
239 columnas existentes: el razonamiento de diseño solo vivía en el código de las
migraciones y en los `@property` de los modelos, invisible para quien abre la
base con un cliente de MySQL.

- 17 comentarios de tabla, 50 comentarios de columna, en las columnas con reglas
  de negocio no evidentes (relaciones `SET NULL` vs `CASCADE` y por qué, "un
  periodo un pago", por qué `monto_cop` se persiste en vez de calcularse, etc.).
- Solo corre en MySQL: SQLite (la conexión de test) no soporta comentarios, y su
  `->change()` reconstruiría la tabla completa — riesgo innecesario para las dos
  columnas generadas del esquema.
- **Verificado:** aplicar, revertir y reaplicar la migración no altera ninguna de
  las 8 claves foráneas de `tareas`, los 2 índices únicos sobre columnas
  generadas, ni la precisión decimal de `pagos`.
- Documentado en [`02-modelo-de-datos.md`](02-modelo-de-datos.md).

## 2. Collation unificado

**Commit:** `da3331e` — `fix(bd): unificar collation en utf8mb4_0900_ai_ci`

El esquema completo había quedado en `utf8mb4_0900_ai_ci` por accidente (el
default de MySQL 8.4 al crear la base sin collation explícito), mientras que las
33 tablas —creadas siempre con collation explícito desde `config/database.php`—
estaban en `utf8mb4_unicode_ci`. Dos collations distintas conviviendo, ninguna
elegida a propósito.

- Todas las tablas y el esquema pasan a `utf8mb4_0900_ai_ci`: el nativo de
  MySQL 8, más preciso y más rápido que `utf8mb4_unicode_ci`.
- `mariadb` en `config/database.php` se queda en `utf8mb4_unicode_ci` a
  propósito: no soporta las collations `_0900_`, y este proyecto corre sobre
  `mysql:8.4`, no sobre MariaDB.
- **Verificado:** aplicar, revertir y reaplicar no toca la columna virtual de
  `periodos`, la columna generada `stored` de `vistas_guardadas`, ni ninguna de
  sus claves foráneas o índices únicos.

## 3. Zona horaria conectada

**Commit:** `8ef9827` — `fix(config): conectar APP_TIMEZONE=America/Bogota, hoy sin efecto`

`config/app.php` tenía `'timezone'` hardcodeado a `'UTC'`: la variable
`APP_TIMEZONE=America/Bogota` del `.env` (y `date.timezone` en `php.ini`) no
hacían nada. `now()` daba siempre UTC, verificado en el contenedor real.

No era cosmético: `CambiarEstadoTarea::ajustarFechas()` fija `fecha_inicio`/
`fecha_fin` con `now()`. Con `app.timezone` en UTC, cualquier tarea movida a
`en_proceso` entre las 7pm y la medianoche hora Bogotá quedaba fechada al día
siguiente, porque en UTC ya había cruzado la medianoche.

- `config/app.php`: `'timezone' => env('APP_TIMEZONE', 'UTC')`.
- `config/database.php`: la conexión MySQL manda `SET time_zone = '-05:00'` a su
  sesión (Bogotá no tiene horario de verano, el offset es fijo).
- Se revisó primero que nada dependiera de que `app.timezone` fuera UTC: cero
  consultas SQL crudas con `NOW()`, todas las fechas viajan al frontend ya
  formateadas con `toDateString()`/`toDateTimeString()`, nunca por la
  serialización automática de Eloquent (que sí fuerza UTC).
- **Verificado:** test nuevo que congela el reloj en un instante UTC que ya
  cruzó la medianoche (01:30 UTC = 8:30pm Bogotá) y confirma que `fecha_inicio`
  queda fechada el día de Bogotá, no el de UTC. Falla sin el fix, confirmado
  manualmente revirtiendo el cambio.

## 4. Script de despliegue automatizado

**Commits:** `e940054`, `3e0b3f6` — `feat(despliegue): ...`

`desplegar.sh`, en la raíz del repositorio. Automatiza el checklist de
`00-entorno.md` en seis fases, cada una con su propia validación:

1. **Entorno** — Docker, Compose v2, curl, puertos 8080/3309/5173 libres.
2. **`.env`** — lo crea si falta, detecta si es la primera vez, alinea UID/GID.
3. **Credenciales de BD** — las pide (ocultas, con confirmación) o las genera
   solas si no hay terminal interactiva.
4. **Construir y levantar** — build, up, `composer install` (el `Dockerfile`
   instala el binario pero no las dependencias — hueco real, invisible en este
   entorno porque `vendor/` ya existía desde antes de dockerizar), espera
   `healthy` en `app`/`nginx`/`mysql`/`node`.
5. **Preparar la aplicación** — `APP_KEY`, migrar, datos de ejemplo (solo si se
   confirma), `storage:link`, hook de git, y el primer administrador.
6. **Validación final** — HTTP 200 en `/up`, sin migraciones pendientes, login
   real verificado.

Termina con un informe (pantalla y `storage/logs/despliegue_<fecha>.log`) que
incluye una tabla de acceso: rol, correo, contraseña y origen del usuario
disponible, o el comando exacto para crear uno si no hay ninguno.

**Verificado de punta a punta**, no solo revisado: contra un clon real y
aislado (sin `vendor/`, sin `node_modules`, sin `.env`, sin volúmenes propios).
Ese proceso encontró y corrigió tres fallos que este entorno de referencia
nunca hubiera revelado por sí solo:

- El hueco de `composer install` ausente del `Dockerfile`.
- El orden de dependencias entre `worker` y las migraciones (`worker` no puede
  quedar sano hasta que exista la tabla `cache`, así que la espera de
  "saludable" se reordenó para excluirlo hasta después de migrar).
- Un bug real de manejo de errores: la función para abortar leía `$?` en una
  línea separada, y el registro del error justo antes ya lo había pisado con su
  propio código de salida — el script seguía adelante después de un fallo que
  debía detenerlo. Corregido con `comando || abortar`, que no depende de leer
  el código de salida por separado.

El flujo interactivo completo (contraseñas + creación del administrador) se
probó con un driver de pty propio (Python, `pty` + `select`) sincronizado por
el texto exacto de cada prompt — una prueba anterior con `script`+un pipe
parecía atascarse tras las contraseñas, y resultó ser una interacción de esa
herramienta con el cambio de modo de eco de la terminal, no un bug real.

Documentado en [`07-despliegue.md`](07-despliegue.md) — casos de uso, qué
valida cada fase, y por qué la base de datos y `root` tienen contraseñas
distintas.

## 5. Roles automáticos y comandos de administración

**Commit:** `3e0b3f6` (junto con el punto anterior)

Con datos vacíos (sin `DesarrolloSeeder`), la base quedaba también sin roles:
`RolesYPermisosSeeder` solo corría si alguien recordaba pasar `--seed`. Sin el
rol `administrador` no hay a quién asignárselo al primer usuario, y no hay
pantalla de registro para crear uno por otra vía.

- **Migración nueva** (`2026_09_04_140000_sembrar_roles_y_permisos`): invoca
  `RolesYPermisosSeeder` desde su `up()`. Como `php artisan migrate` es lo único
  que cualquier forma de desplegar este proyecto corre siempre, los roles
  quedan sembrados sin depender de `desplegar.sh` en absoluto. `down()` no
  revierte a propósito: borrar roles ya asignados sería un efecto secundario
  más grave que lo que un rollback normalmente implica.
- **`php artisan admin:crear`** (`{--nombre=} {--correo=} {--clave=}`) — crea o
  promueve (idempotente por correo, `updateOrCreate`) al primer administrador.
- **`php artisan admin:verificar`** (`{--correo=} {--clave=}`) — comprueba un
  login con `Auth::once()`, sin dejar sesión abierta.

Los dos comandos reciben la contraseña por opción (argv), nunca interpolada en
una cadena de PHP: la validación final de `desplegar.sh` pasaba antes por
`tinker --execute="..."` con el correo y la clave metidos directamente en el
código a ejecutar, y una contraseña tipeada a mano con una comilla simple
rompía el comando en vez de solo fallar el login.

**Verificado:** cada comando con su test (`CrearAdministradorTest`,
`VerificarCredencialesTest` — creación, idempotencia, promoción de un usuario
existente, contraseña débil rechazada, correo inválido rechazado, contraseña
con comillas manejada sin romper el comando).

Documentado en [`docs/modulos/autenticacion.md`](modulos/autenticacion.md#el-primer-administrador-cómo-se-sale-del-huevo-y-la-gallina).

## 6. Compatibilidad con Fedora/RHEL (SELinux)

**Commit:** `526d124` — `fix(docker): bind mount de código con :z, requisito real en Fedora/RHEL`

Encontrado al confirmar si el proyecto ya se podía clonar y desplegar en una
máquina Fedora. `docker-compose.yml` montaba el código
(`.:/var/www/html`) sin etiqueta de SELinux, compartido entre cinco servicios a
la vez. En un host con SELinux en modo `enforcing` —el default en Fedora
Workstation/Server y en RHEL, no en este entorno de referencia ni en
WSL2/Ubuntu/Debian— eso deniega el acceso con `Permission denied` en cuanto un
contenedor intenta leer o escribir ahí, sin que el error apunte a la causa.

- `.:/var/www/html:z` en los 5 servicios que lo montan (`app`, `worker`,
  `node`, `nginx`, `humo`).
- `:z` minúscula, no `:Z`: `:Z` relabela el mount como exclusivo de un solo
  contenedor y rompería a los otros cuatro que comparten el mismo path.
- `desplegar.sh` detecta SELinux `Enforcing` (`getenforce`) y lo deja constando
  en su informe como algo ya resuelto, no como una advertencia.
- **Verificado:** en un host sin SELinux la etiqueta no hace nada — suite
  completa en verde antes y después, con un `docker compose up -d` real
  recreando los cinco contenedores.

## 7. Documentación puesta al día

**Commit:** `c0c3404` — `docs: consolidar el contexto real de la sesión de mantenimiento`

Auditoría explícita, pedida antes de seguir: verificar que los seis cambios
anteriores quedaran reflejados en la documentación que alguien va a leer en la
próxima fase, no solo en los mensajes de commit. Encontró seis huecos reales,
dos de ellos activamente engañosos (llevaban a un despliegue roto si se
seguían tal cual):

| Documento | Qué tenía mal o le faltaba |
|---|---|
| `README.md`, `docs/00-entorno.md` | El paso a paso manual no tenía `composer install` ni `storage:link` (hueco preexistente, no de esta sesión), y no mencionaban `desplegar.sh` |
| `docs/modulos/autenticacion.md` | La regla "las cuentas las crea el administrador" no explicaba qué pasa cuando no hay ninguno todavía |
| `docs/02-modelo-de-datos.md` | Sin mención de los comentarios de tabla ni de la collation unificada |
| `docs/06-historial-de-decisiones.md` | Sin ninguna entrada de esta sesión |
| `.ai/rules/seeders.md`, `.ai/rules/migrations.md` | Sin la advertencia de que `RolesYPermisosSeeder` también lo invoca una migración, ni el patrón nuevo (datos de referencia idempotentes desde una migración) |

Las dos reglas de `.ai/` se registraron con la herramienta `record-rule` de
Boost, no editadas a mano, para que queden en el formato que un agente carga
al tocar esos archivos.

## Verificación general de la sesión

- Suite completa (`php artisan test`) en verde después de cada commit, sin
  excepción: nunca se comiteó con la suite en rojo.
- Los tres cambios de esquema (comentarios, collation, la migración de roles)
  se probaron contra MySQL real, no solo contra SQLite: aplicar, revertir y
  reaplicar cada uno, verificando estructura con `SHOW CREATE TABLE` antes y
  después.
- `desplegar.sh` se probó de punta a punta contra un clon real y aislado en
  tres escenarios: primera instalación (no interactiva), primera instalación
  (interactiva, con creación de administrador), y re-ejecución idempotente
  sobre un entorno ya inicializado.
- `vendor/bin/pint --dirty` en verde en cada commit con cambios PHP.

## Lo que queda pendiente

- **`.env.example`** sigue con `DB_PASSWORD`/`DB_ROOT_PASSWORD` vacíos. Se
  discutió prellenarlos con los mismos valores de respaldo que ya trae
  `docker-compose.yml` (`secret`/`root`), pero quedó resuelto de otra forma:
  `desplegar.sh` los pide o los genera solo, así que la simplificación de
  `.env.example` dejó de ser necesaria y no se implementó.
- Ninguno de estos siete commits pasó por una rama `fase/NN` ni quedó
  etiquetado — ver la nota al principio de la sección correspondiente en
  [`06-historial-de-decisiones.md`](06-historial-de-decisiones.md#mantenimiento-post-fase-12).
