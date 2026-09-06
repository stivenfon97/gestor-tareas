# Historial de decisiones

Las decisiones que se tomaron sobre la marcha, con su porqué y —esto es lo que de verdad
importa— las alternativas que se descartaron y por qué. Un listado de rutas se regenera
con `php artisan route:list`; el razonamiento detrás de una decisión no se recupera de
ningún sitio si no queda escrito.

Las reglas de negocio numeradas (`D1.1`, `D4.8`…) viven en
[`03-dominio.md`](03-dominio.md); este documento no las repite, las referencia y añade lo
que ese documento no cubre: decisiones de infraestructura, herramientas y proceso.
Organizado por la fase en la que se tomó cada una.

---

## Fase 01–02 — Fundación

### Docker propio, no Laravel Sail

**Decisión:** cinco servicios (`app`, `nginx`, `mysql`, `node`, `worker`) definidos a
mano en `docker-compose.yml`, con `laravel/sail` instalado pero sin usar.

**Por qué:** Sail asume Nginx y límites de subida genéricos. Este proyecto necesita
control explícito de `client_max_body_size` (respaldos `.sql` de hasta 256 MB, D8.1) y de
la relación entre `docker/php/php.ini` y `docker/nginx/default.conf` — cuatro sitios que
tienen que moverse juntos (ver la skill `archivos-y-adjuntos` en `.ai/skills/` y
[`00-entorno.md`](00-entorno.md#limites-de-subida)). Sail lo esconde detrás de comandos
que no dan ese control fino.

**Alternativa descartada:** quitar `laravel/sail` del todo. Se decidió dejarlo instalado
y sin usar: quitarlo no aporta nada (no estorba, no se ejecuta, no aparece en ningún
flujo) y el `composer.json` documenta la razón. Ver también la nota en
`docs/00-entorno.md`: "No se usa Laravel Sail: se necesita control explícito de Nginx y
de los límites de subida."

### `DB_PORT=3308`, no 3306

**Decisión:** `mysqld` escucha en el puerto 3308 dentro de la red de Compose y se
publica en el 3309 del host.

**Por qué:** la máquina de desarrollo ya tiene otro proyecto con un MySQL propio en el
3307. Cambiar el puerto interno evita que dos proyectos compartan servidor o que uno
tumbe al otro por error al levantar los dos a la vez.

**Alternativa descartada:** dejar el 3306 por defecto y resolver el choque solo en el
mapeo de puertos del host. Se descartó porque el número por defecto sigue siendo una
trampa para quien copie fragmentos de configuración entre los dos proyectos; mover
también el puerto interno hace el error imposible, no solo raro.

### Colas, caché y sesión por `database`, no por Redis

**Decisión:** `QUEUE_CONNECTION`, `CACHE_STORE` y `SESSION_DRIVER` van los tres por el
driver `database`. No hay Redis ni Horizon en el stack.

**Por qué:** es una herramienta de un freelance para su propio trabajo, no un sistema con
tráfico que justifique la complejidad operativa de otro servicio con su propio volumen,
su propio healthcheck y su propia persistencia que respaldar. La cola solo mueve el envío
de notificaciones y trabajos ligeros; MySQL ya está ahí y ya se respalda (`make
respaldo`, fase 12).

**Alternativa descartada:** añadir Redis "por si acaso escala". Se descartó
explícitamente y quedó registrado como regla (`.ai/rules/general.md`): "no añadas
variables `REDIS_*` ni cambies estos drivers". Añadirlo sin una necesidad real es la
clase de complejidad que nadie vuelve a quitar.

---

## Fase 03 — Migraciones, modelos y enums

### Enums de PHP, no tablas de catálogo

**Decisión:** los conjuntos cerrados del dominio (`EstadoTarea`, `TipoTarea`,
`PrioridadTarea`, `RolProyecto`, `RolGlobal`, `Visibilidad`…) son enums de PHP con
comportamiento, no filas en una tabla `estados` con una FK.

**Por qué:** las preguntas que se hacen sobre estos valores —¿a qué estados puedo pasar
desde aquí?, ¿qué prioridad trae por defecto este tipo?— son de código, no de datos. Una
tabla de catálogo convertiría `EstadoTarea::transicionesPermitidas()` en un `JOIN` contra
una tabla de reglas, y la matriz de once transiciones dejaría de poder validarse en un
test unitario puro (`tests/Unit/MatrizDeTransicionesTest.php`).

**Alternativa descartada:** tabla de catálogo con un campo `metadata` JSON para las
reglas de transición. Técnicamente posible, pero traslada a runtime (y a datos, no a
código revisable en un PR) una decisión que no cambia con el tiempo: nadie va a añadir un
séptimo estado de tarea sin tocar también la interfaz, los informes y la documentación.

### Columnas de estado en `varchar`, no `ENUM` de MySQL

**Decisión:** `estado`, `tipo`, `prioridad`, etc. son `varchar` con el enum de PHP como
cast, no el tipo `ENUM` nativo de MySQL.

**Por qué:** alterar un `ENUM` de MySQL reconstruye la tabla entera, y
`historial_estados` necesita poder seguir guardando valores que el enum de PHP deje de
tener (un estado que se retire algún día no debería romper el historial de quien ya pasó
por él). Documentado en `docs/01-arquitectura.md#enums-de-php-en-lugar-de-tablas-de-catálogo`.

---

## Fase 06 — Núcleo de tareas y transiciones

### `entregada` es terminal (D2.6)

**Decisión:** una tarea `entregada` no admite ninguna transición de salida. Un fallo
posterior no la reabre.

**Por qué:** reabrir perdería **cuándo se entregó de verdad** la primera vez, y
mezclaría en una sola fila el trabajo original con la corrección — dos cosas que en la
facturación por periodos son distintas: la entrega cuenta en el periodo en que se
entregó, la corrección cuenta en el periodo en que se hizo, y pueden ser periodos
distintos. En vez de reabrir, se crea una tarea nueva de tipo `correccion` vinculada a la
original vía `tarea_origen_id` (D3.3).

**Alternativa descartada:** `entregada → en_proceso` como una transición más de la
matriz, igual que existe `terminada → en_proceso` para cuando falla la revisión antes de
desplegar. Se descartó porque las dos situaciones no son la misma: una revisión que falla
antes de entregar es parte del mismo ciclo de trabajo; un fallo **después** de entregar es
un hecho nuevo que el cliente ya vio, y el reporte del periodo en que se entregó tiene que
seguir diciendo la verdad sobre lo que se entregó ese día. Reabrir la tarea original
reescribiría esa historia.

### El traslado entre periodos no pasa por `CambiarEstadoTarea` (D4.10)

**Decisión:** al cerrar un periodo, las tareas que se trasladan al siguiente actualizan
`periodo_id`/`periodo_origen_id` con un `update()` de query builder en `CerrarPeriodo`,
nunca instanciando el servicio de transiciones de estado.

**Por qué:** el traslado no cambia el `estado` de la tarea — una tarea `en_proceso` sigue
`en_proceso` en el periodo nuevo. `CambiarEstadoTarea` existe para validar y registrar
**cambios de estado**; llamarlo aquí con el mismo estado de origen y destino ensuciaría
`historial_estados` con una fila que dice "pasó de `en_proceso` a `en_proceso`", una
transición que en los hechos nunca ocurrió. El historial dejaría de ser una lectura fiel
de lo que le pasó a la tarea.

**Alternativa descartada:** extender `CambiarEstadoTarea` con un modo "sin cambio de
estado, solo actualizar periodo" para reutilizar su transacción y su registro. Se
descartó porque mezclaría dos preocupaciones distintas en un solo servicio —transición de
estado y traslado de periodo— y el día que alguien leyera `historial_estados` esperando
solo transiciones reales, tendría que empezar a filtrar ruido.

---

## Fase 07–08 — Identidad visual y vistas

### Token semántico en vez de utilidad de Tailwind cruda

**Decisión:** todo color en un componente es un token (`bg-estado-pendiente-fondo`), y
nunca un hex ni una utilidad de paleta como `bg-yellow-100`.

**Por qué:** un literal como `bg-yellow-100` no tiene par oscuro; en modo oscuro el
resultado es texto claro sobre fondo claro. Un token define los dos a la vez y el tema
decide. Detallado en `docs/05-identidad-visual.md`.

**Alternativa descartada:** una función helper en JS que reciba el color base y calcule
su variante oscura en tiempo de ejecución. Se descartó por la misma razón que impide
`` `bg-${variable}` ``: el escáner de Tailwind 4 lee el código como texto estático, y una
clase que no aparece completa y literal en el código fuente no se genera — funcionaría en
desarrollo (JIT más permisivo) y fallaría en el build de producción, mucho después de que
alguien lo diera por terminado.

### Vistas guardadas privadas, nunca compartidas

**Decisión:** una vista guardada pertenece a un usuario. Ni siquiera un administrador
alcanza la de otro (`Gate::before` no aplica aquí a propósito).

**Por qué:** una vista guardada es una preferencia de lectura, no un dato del proyecto.

**Alternativa descartada:** compartirlas, con o sin permiso de edición. Suena razonable y
no lo es: dos personas con la misma vista guardada acaban discutiendo sobre qué debería
mostrar. El día que de verdad haga falta compartir una configuración de filtros, se añade
un campo — hoy la ausencia de ese campo es la decisión, no un olvido.

### "Una sola predeterminada" la garantiza la base de datos, no la interfaz

**Decisión:** tanto la vista guardada predeterminada (fase 08) como el periodo abierto
único por proyecto (fase 09, D4.8) usan el mismo mecanismo: una columna **generada** que
vale un identificador cuando la fila cumple la condición y `NULL` en cualquier otro caso,
más un índice único.

**Por qué:** ni MySQL ni SQLite tienen índices únicos *parciales* (un índice que solo
mira las filas que cumplen una condición), pero los dos motores **ignoran `NULL`** en un
índice único — varias filas con `NULL` conviven sin chocar, y dos filas con el mismo
valor no-nulo sí chocan. Una columna generada `= id_relevante` cuando la condición es
cierta, `NULL` cuando no, convierte "solo una fila puede cumplir esto" en una restricción
de la base de datos, no en una promesa de la interfaz.

**Alternativa descartada:** dejarlo en manos del controlador (desmarcar la anterior antes
de marcar la nueva) o de un observer. Se mantiene como *cortesía* en el controlador —
evita que el usuario tenga que desmarcar a mano—, pero **no es la cerradura**: dos
pestañas del mismo usuario abiertas a la vez, un comando de consola o un seeder mal
escrito podrían dejar dos filas marcadas, y entonces "la predeterminada" pasaría a ser la
que el motor devuelva primero — un valor que cambia solo, sin que nadie lo haya decidido.

### `VIRTUAL`, no `STORED`, para la columna generada de periodos (D4.8)

**Decisión:** `periodos.proyecto_si_abierto` (la columna generada que garantiza un solo
periodo abierto por proyecto) usa `virtualAs()`, mientras que la de vistas guardadas
(`vistas_guardadas.marca_predeterminada`, fase 08) usa `storedAs()`.

**Por qué:** son el mismo patrón pero no la misma situación. La expresión de vistas
guardadas depende de una columna booleana sin clave foránea (`es_predeterminada`); la de
periodos depende de `proyecto_id`, que **sí tiene** una clave foránea
(`periodos_proyecto_id_foreign`). En MySQL 8.4, una columna generada `STORED` que
depende de una columna con clave foránea hace que el `ALTER TABLE` falle con
`Cannot add foreign key constraint` (error 1215) — aunque la sentencia no toque ninguna
FK. Es una limitación del algoritmo que InnoDB usa para reconstruir la tabla al añadir la
columna, no una comprobación de integridad real: persiste incluso con
`FOREIGN_KEY_CHECKS=0`. `VIRTUAL` no reconstruye filas al crearse y no tropieza con esa
limitación; MySQL indexa columnas virtuales sin problema desde la versión 5.7, y SQLite
(donde corre la suite de pruebas) las soporta igual que las `STORED`.

**Alternativa descartada:** quitar temporalmente la clave foránea, añadir la columna
`STORED` y volver a crear la FK. Funciona, pero convierte una migración de una sentencia
en tres, con una ventana entre medias sin la restricción de integridad — un costo
innecesario cuando `VIRTUAL` resuelve exactamente el mismo caso sin ese rodeo. Detalle
completo, con el error exacto, en el comentario de cabecera de
`database/migrations/2026_09_02_120000_garantizar_un_periodo_abierto_por_proyecto.php`.

### `make humo`: la excepción acotada a "sin pruebas de navegador"

**Decisión:** existe una suite de Playwright (`tests/humo/`) que recorre catorce
pantallas a tres anchos, en claro y oscuro (y, desde la fase 12, una vez en inglés), y
comprueba exactamente dos cosas por pantalla más dos defectos de composición
(fase 07d): que no aparece texto con forma de clave sin traducir, y que el documento no
desborda a lo ancho.

**Por qué:** las fases 04 y 07 se cerraron **en verde** con la aplicación mostrando
`nav.dashboard` en pantalla en vez de "Tablero". `npm run build` compila igual de bien
con las claves sin resolver — el compilador no sabe qué significa `es-CO`, solo empaqueta
JavaScript — y ninguna prueba de servidor mira el HTML que termina en el navegador,
porque Inertia pinta el contenido **en el cliente**: lo que `php artisan test` ve es el
JSON de props, no el texto que un humano lee. Lo mismo pasa con el desbordamiento
horizontal: es una consecuencia de cómo el navegador reparte el ancho disponible entre
columnas, algo que no existe hasta que un motor de layout real lo calcula.

**Alternativa descartada — convertirla en una suite end-to-end completa.** Se decidió
explícitamente que **no crezca** más allá de esas comprobaciones: nada de probar flujos,
formularios ni aserciones de negocio ahí. Eso ya lo hace Pest, es rápido, y mezclar las
dos cosas en Playwright (lento, necesita los servicios levantados, un solo worker por los
límites de acceso de Fortify) daría una suite lenta de mantener sin ganar cobertura real.
La regla quedó registrada en `.ai/rules/tests.md`: *"No es una suite end-to-end y no debe
crecer hasta serlo"*.

---

## Fase 09 — Periodos, pagos y TRM

### `monto_cop` se persiste calculado, no se deriva al leer (D4.6)

**Decisión:** `PagoObserver::saving()` recalcula `monto_cop = monto_usd × tasa_cambio`
(con `bcmul` sobre cadenas, nunca en coma flotante) y lo guarda en la fila, en vez de
calcularlo cada vez que se lee.

**Por qué:** corregir mañana la tasa de un pago de hace tres meses no debe reescribir lo
que ya se cobró entonces. Si `monto_cop` se derivara al leer, cambiar `tasa_cambio` en un
pago viejo (por ejemplo, al corregir un error de captura) cambiaría silenciosamente el
histórico de ingresos de un periodo ya cerrado y reportado.

**Alternativa descartada:** guardar solo `monto_usd` y `tasa_cambio`, y calcular
`monto_cop` en el modelo con un accessor. Más simple de mantener sincronizado, pero
convierte cualquier corrección posterior de la tasa en una reescritura retroactiva del
ingreso — exactamente lo que esta decisión evita.

### `ConsultarTrm`: ninguna llamada externa bloquea el formulario

**Decisión:** la consulta a la API de tasa de cambio tiene un tiempo de espera corto (3
segundos), un solo intento (sin reintentos) y cualquier fallo —caída, timeout, forma de
respuesta inesperada— se traga devolviendo `null`, nunca una excepción que interrumpa el
registro del pago.

**Por qué:** el formulario de registrar un pago no debería depender de que un servicio de
terceros esté arriba. Si la API no responde, `origen_tasa` queda como `manual` y quien
registra el pago escribe la tasa a mano — sigue pudiendo cerrar su trabajo.

**Alternativa descartada:** reintentar varias veces antes de rendirse (`retry()` de
Laravel). Se descartó porque una API caída no se arregla reintentando en el mismo
segundo, y cada reintento es tiempo de espera real que el usuario nota en el formulario.
También se descartó cachear el fallo (para no volver a intentarlo pronto): solo se
cachea un valor **resuelto**, nunca un fallo, para que una caída puntual de la API no deje
una clave "envenenada" el resto del día sin que nadie vuelva a intentarlo.

---

## Fase 11 — Reportes Excel

### El snapshot va desnormalizado: texto resuelto, nunca ids (D7.3, D7.6)

**Decisión:** `reportes.snapshot` guarda las filas **exactas** que se exportaron —nombre
de módulo, etiqueta de estado ya traducida, nombre y descripción de la tarea— como texto
literal, nunca como `modulo_id`, el valor crudo de un enum, o cualquier otro identificador
que haya que resolver después.

**Por qué:** la trazabilidad tiene que sobrevivir a que el mundo siga cambiando. Si el
snapshot guardara `modulo_id`, renombrar ese módulo en marzo cambiaría silenciosamente lo
que el reporte de enero dice haber mostrado — y la pregunta que el snapshot existe para
responder, *"¿qué vio el cliente exactamente ese día?"*, dejaría de tener una respuesta
fiable. Desnormalizar es precisamente lo que hace que "histórico" signifique histórico:
una foto, no una vista que se recalcula.

**Alternativa descartada:** guardar los ids y resolver el texto en el momento de la
descarga histórica, igual que se hace para la descarga "actual". Es la opción obvia y es
exactamente la que se descartó: mezclaría los dos modos de descarga (D7.5) —uno que debe
reflejar el estado de hoy y otro que debe congelar el de ayer— detrás de la misma
consulta, y el día que alguien cambiara sin querer el modo equivocado, nadie lo notaría
hasta que un cliente preguntara por qué el reporte de julio ahora dice otra cosa.

### El reporte de cliente no *omite* la hoja de pagos: nunca tiene esa clave

**Decisión (D7.6):** `GenerarReporte` construye una estructura de snapshot **distinta**
según el tipo de reporte. La de tipo `cliente` no tiene las claves `resumen`, `notas` ni
`pagos` en absoluto — no es que `HojaClienteExport` decida no pintarlas.

**Por qué:** "no hay dato que omitir en el Excel porque la fila nunca lo tuvo" es una
garantía estructural; "hay un dato pero elegimos no mostrarlo" es una decisión que un
cambio futuro puede deshacer sin que nadie se dé cuenta, con solo añadir una columna a la
hoja equivocada. La prueba que de verdad protege esto
(`tests/Feature/Reportes/ReporteClienteSinPagosTest.php`, endurecida en la fase 12 para
comparar valores completos y no fragmentos cortos, ver ese cierre de fase) no confía en
que el código actual sea cuidadoso: abre el `.xlsx` generado y recorre cada celda de cada
hoja buscando cualquier rastro del pago.

**Alternativa descartada:** una sola estructura de snapshot con todos los datos, y que
cada `Export` filtre qué hojas pintar según el tipo de reporte. Es más simple de escribir
y es justo el diseño que D7.6 rechaza: pone la seguridad en manos de que cada nueva clase
de exportación recuerde no leer `pagos`, en vez de hacer imposible que la lea porque el
dato no está.

---

## Fase 12 — Dashboard, respaldo, i18n y cierre

### El respaldo es un solo `.tar.gz`, no dos archivos sueltos

**Decisión:** `make respaldo` produce un único archivo fechado con dos miembros dentro
(`basedatos.sql.gz` y `archivos.tar.gz`), no dos descargas independientes.

**Por qué:** la base de datos y los archivos subidos son, para efectos de restaurar, una
sola unidad — una fila de `respaldos_bd` que apunta a un adjunto que no está en el
`archivos.tar.gz` (porque alguien se llevó solo la base) es tan inútil como un adjunto
sin la fila que lo describe. Un solo archivo hace estructuralmente imposible restaurar
la mitad por error.

**Alternativa descartada:** dos comandos (`make respaldo-bd`, `make respaldo-archivos`)
que el usuario combina a mano cuando hace falta. Se descartó porque delega a la memoria
de una persona, en el peor momento posible —recuperándose de un desastre—, algo que un
solo comando puede garantizar siempre.

### El idioma se persiste en cookie, no en una columna de `users`

**Decisión:** la preferencia de idioma vive en la cookie `idioma` (sin cifrar, 365 días),
leída por `App\Http\Middleware\HandleIdioma` — el mismo patrón que ya usaba `appearance`
para el tema desde la fase 08.

**Por qué:** el texto de la interfaz viaja siempre como **clave** de traducción, nunca
resuelto desde el servidor (`enums.estado_tarea.pendiente`, no "Pendiente" — ver
`.ai/rules/js.md`), así que cambiar de idioma no necesita ningún viaje al servidor: el
cliente vuelve a resolver las mismas claves con otro diccionario
(`loadLanguageAsync()`). Una columna en `users` habría sido la opción "correcta" para una
aplicación multiusuario con perfiles ricos, pero habría exigido una migración, una ruta
de actualización y una policy, para persistir algo que el navegador ya persiste solo con
una cookie — y que, a diferencia del tema, ni siquiera decide *ninguna consulta*: solo
qué diccionario cargar y qué locale usa Carbon.

**Alternativa descartada:** guardar el idioma en la sesión (`session(['idioma' => ...])`)
en vez de una cookie. Se descartó porque la sesión no sobrevive a que el usuario borre
las cookies de sesión o cambie de navegador en el mismo equipo, mientras que una cookie
de 365 días sí — y es exactamente lo que ya se decidió para el tema, con el mismo
razonamiento.

### `Carbon::setLocale()` junto a `app()->setLocale()`

**Decisión:** `HandleIdioma` fija los dos locales, no solo el de Laravel.

**Por qué:** Laravel no sincroniza el locale de Carbon automáticamente solo con
`app()->setLocale()`. Sin este segundo paso, `diffForHumans()` —usado en la pantalla de
seguridad de passkeys— seguía saliendo siempre en inglés (el locale por defecto de
Carbon) sin importar en qué idioma estuviera el resto de la interfaz, un defecto que solo
se hizo visible al hacer el recorrido de `make humo` en inglés (fase 12) y notar que la
regla "todo el texto viaja como clave" tenía esta única excepción real, oculta dentro de
una librería de terceros.

---

## Mantenimiento post-fase-12

Las siguientes decisiones no llevan número de fase: se tomaron después del cierre del
proyecto (fase 12), directamente sobre `main`, a petición explícita en cada caso —no
siguieron el flujo de rama por fase que describe
[`04-control-de-versiones.md`](04-control-de-versiones.md)—. Se documentan aquí porque
el criterio del documento (decisiones no obvias, con su porqué y sus alternativas) no
depende de que exista una rama.

### Collation `utf8mb4_0900_ai_ci`, no `utf8mb4_unicode_ci` ni `utf8mb4_general_ci`

**Decisión:** las 33 tablas y el esquema completo pasan de `utf8mb4_unicode_ci` (el
default de Laravel, en las tablas) y `utf8mb4_0900_ai_ci` (el default de MySQL 8.4, que
había quedado por accidente en el esquema, sin que nadie lo pidiera) a
`utf8mb4_0900_ai_ci` en todas partes, de forma consistente.

**Por qué:** dos collations distintas convivían en el mismo esquema sin que ninguna
fuera una elección — un accidente de cómo Docker crea la base de datos la primera vez.
`utf8mb4_0900_ai_ci` es el collation nativo de MySQL 8: más preciso y más rápido que
`utf8mb4_unicode_ci`, y evita el riesgo de "Illegal mix of collations" si algún día una
consulta cruza por texto dos tablas con collations distintas.

**Alternativa descartada:** `utf8mb4_general_ci`, el estándar habitual de quien pidió el
cambio en otros proyectos. Se descartó porque es la opción más antigua y menos precisa
de las tres disponibles — no hay motivo técnico para preferirla sobre la nativa de
MySQL 8 en un proyecto que ya corre sobre `mysql:8.4`.

### `APP_TIMEZONE` conectado, pero `created_at`/`updated_at` se quedan en UTC

**Decisión:** `config/app.php` pasa de `'timezone' => 'UTC'` (hardcodeado, ignorando la
variable de entorno) a `'timezone' => env('APP_TIMEZONE', 'UTC')`, y la conexión MySQL
manda `SET time_zone = '-05:00'` a su sesión. Ninguna fecha existente se reinterpreta:
el cambio es hacia adelante.

**Por qué:** `APP_TIMEZONE=America/Bogota` llevaba en `.env` desde siempre sin hacer
nada. El síntoma concreto: `CambiarEstadoTarea::ajustarFechas()` fija `fecha_inicio`/
`fecha_fin` con `now()`, y con `app.timezone` en UTC, cualquier tarea movida a
`en_proceso` entre las 7pm y la medianoche hora Bogotá quedaba fechada al día
siguiente, porque en UTC ya había cruzado la medianoche. Se verificó primero que nada
en el código dependiera de que `app.timezone` fuera UTC (ninguna consulta SQL cruda con
`NOW()`, todas las fechas viajan al frontend ya formateadas con
`toDateString()`/`toDateTimeString()`, nunca por la serialización automática de
Eloquent que sí fuerza UTC) antes de cambiarlo.

**Por qué no se tocó también la serialización de `created_at`/`updated_at`:** Laravel
las fuerza a UTC siempre, sin importar `app.timezone` — es un comportamiento
documentado, no un descuido. Revertirlo habría exigido pelear contra el framework para
un beneficio marginal: esas dos columnas son metadatos técnicos, no una fecha que el
usuario lea directamente.

**Alternativa descartada:** dejar `app.timezone` en UTC y corregir `now()` por
`now('America/Bogota')` solo en los sitios puntuales donde importa el día calendario
de Bogotá. Se descartó tras confirmar que no había ningún otro código que dependiera de
que `app.timezone` fuera UTC específicamente: el cambio global es más simple y cierra
la puerta a que aparezca el mismo bug en el próximo sitio que use `now()` sin pensar en
la zona horaria.

### Roles y permisos sembrados desde una migración, no solo desde un seeder

**Decisión:** `RolesYPermisosSeeder` —ya separado a propósito del seeder de datos de
ejemplo desde antes— ahora además se invoca desde la migración
`2026_09_04_140000_sembrar_roles_y_permisos`, en su `up()`.

**Por qué:** un despliegue con datos vacíos (sin `DesarrolloSeeder`) se quedaba también
sin roles, porque `migrate --seed` corre las dos cosas juntas o ninguna. Sin el rol
`administrador` no hay a quién asignárselo al primer usuario, y no hay pantalla de
registro para crear uno por otra vía. Como `php artisan migrate` es lo único que
cualquier forma de desplegar este proyecto corre siempre —`desplegar.sh`, `make
migrate` a secas, CI—, ponerlo en una migración lo hace automático sin depender de que
nadie recuerde una bandera `--seed` ni el nombre de una clase.

**Alternativa descartada:** que `desplegar.sh` llamara a `RolesYPermisosSeeder`
explícitamente, sin importar la respuesta a "¿cargar datos de ejemplo?". Se descartó
porque solo resolvía el problema para quien usa ese script — cualquier otro método de
desplegar el proyecto seguía sin roles.

### El bind mount del código lleva `:z`, por SELinux en Fedora/RHEL

**Decisión:** `.:/var/www/html` pasa a `.:/var/www/html:z` en los cinco servicios que lo
montan (`app`, `worker`, `node`, `nginx`, `humo`).

**Por qué:** en un host con SELinux en modo `enforcing` —el valor por defecto en Fedora
Workstation/Server y en RHEL, no en este entorno de referencia ni en WSL2/Ubuntu/
Debian—, un bind mount sin etiquetar queda bloqueado: cualquier contenedor que intente
leer o escribir ahí recibe "Permission denied" sin que el error apunte a la causa. Salió
a la luz al confirmar si el proyecto ya se podía clonar y desplegar en una máquina
Fedora.

**Por qué `:z` minúscula y no `:Z`:** `:Z` relabela el mount como exclusivo de un solo
contenedor. Como los cinco servicios comparten este mismo bind mount, `:Z` habría hecho
que el primer contenedor en arrancar se quedara con el acceso y bloqueara a los otros
cuatro. `:z` es precisamente la opción para un mount compartido entre varios
contenedores.

**Alternativa descartada:** decirle al usuario que baje SELinux a modo `permissive` o lo
desactive. Se descartó por ser una degradación de seguridad de todo el sistema para
resolver un problema de un solo bind mount, cuando la solución correcta (`:z`) no tiene
ningún costo en un host sin SELinux.

---

## Ver también

- [`03-dominio.md`](03-dominio.md) — las reglas de negocio numeradas que varias de estas
  decisiones implementan.
- [`01-arquitectura.md`](01-arquitectura.md) — las decisiones estructurales de más largo
  alcance (Inertia frente a API+SPA, enums frente a catálogo, dónde vive cada capa).
- `.ai/rules/` — el mismo razonamiento, en el formato que un agente carga al editar cada
  archivo.
