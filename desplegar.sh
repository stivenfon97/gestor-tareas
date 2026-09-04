#!/usr/bin/env bash
#
# Despliega el proyecto de cero, o lo deja al día si ya estaba configurado.
#
# Valida que el entorno tenga lo necesario, pide (o genera) las credenciales
# de la base de datos, construye y levanta los cinco servicios, prepara la
# base de datos (migraciones y, si aplica, datos de ejemplo) y comprueba al
# final que la aplicación responde y que se puede iniciar sesión.
#
# Uso:
#   ./desplegar.sh
#
# Documentación completa —qué valida cada paso, cómo leer el informe final,
# y los casos de uso (primera instalación, reinstalación, entorno sin
# datos de ejemplo)— en docs/07-despliegue.md.
#
# Nada de lo que hace este script es exclusivo de él: son los mismos pasos de
# docs/00-entorno.md y del Makefile, encadenados con validación y un informe
# al final. No reemplaza `make`; sigue funcionando make up/migrate/hooks
# sueltos para el día a día.

set -uo pipefail
trap 'echo; echo "Cancelado por el usuario." >&2; exit 130' INT

# --------------------------------------------------------------------------
# Configuración
# --------------------------------------------------------------------------

# Puertos que docs/00-entorno.md documenta como fijos. Si el proyecto se
# reconfiguró para usar otros, actualízalos aquí también.
PUERTOS_ESPERADOS=(8080 3309 5173)

# Credenciales que crea DesarrolloSeeder. Solo se usan para la comprobación
# de login al final, y solo si de verdad se cargaron seeders (o si ya
# existían de una ejecución anterior): un fallo aquí en un entorno sin
# seeders es normal, no un error del despliegue.
CORREO_PRUEBA="admin@gestor.test"
CLAVE_PRUEBA="password"

DC="docker compose"
FECHA_INICIO=$(date +%Y-%m-%d_%H%M%S)
ARCHIVO_INFORME="storage/logs/despliegue_${FECHA_INICIO}.log"

# Interactivo solo si stdin es una terminal real. Sin TTY (CI, un pipe) no
# tiene sentido bloquear esperando una contraseña que nadie va a escribir.
if [ -t 0 ]; then
    INTERACTIVO=true
else
    INTERACTIVO=false
fi

# --------------------------------------------------------------------------
# Utilidades de registro e informe
#
# Cada paso se guarda en PASOS como "ESTADO|descripcion|detalle" para poder
# imprimir el informe final y escribirlo en disco con el mismo contenido.
# ESTADO es OK, ADVERTENCIA o ERROR.
# --------------------------------------------------------------------------

PASOS=()

registrar_ok() {
    PASOS+=("OK|$1|")
    echo "  ✅ $1"
}

registrar_advertencia() {
    PASOS+=("ADVERTENCIA|$1|${2:-}")
    echo "  ⚠️  $1"
}

registrar_error() {
    PASOS+=("ERROR|$1|${2:-}")
    echo "  ❌ $1" >&2
    if [ -n "${2:-}" ]; then
        echo "     Detalle: ${2}" >&2
    fi
}

# Título de fase, para que la salida en pantalla se lea por bloques.
fase() {
    echo ""
    echo "── $1 ──"
}

# Ejecuta un comando, captura su salida completa (stdout+stderr) y registra
# el resultado. Uso: ejecutar "descripcion" comando arg1 arg2...
# Devuelve el código de salida real del comando (0 si fue bien), para que
# el que llama decida con `||` si debe abortar. Nunca se lee $? por
# separado en una línea aparte: entre medias puede correr otra cosa
# (incluida esta misma función) y pisarlo.
ejecutar() {
    local descripcion=$1
    shift
    local salida
    if salida=$("$@" 2>&1); then
        registrar_ok "$descripcion"
        return 0
    else
        local codigo=$?
        registrar_error "$descripcion" "$salida"
        return "$codigo"
    fi
}

# Aborta el script ya, con el informe escrito a disco. Se llama con
# `comando || abortar`, nunca suelto en su propia línea: así no depende de
# leer $? por separado, que es justo lo que falló en una versión anterior
# de este script (un registro de error de por medio pisaba el código real
# y el despliegue seguía adelante después de un fallo que debía pararlo).
abortar() {
    escribir_informe
    echo ""
    echo "El despliegue se detuvo. Revisa el detalle del error arriba" >&2
    echo "y el informe completo en: $ARCHIVO_INFORME" >&2
    exit 1
}

escribir_informe() {
    mkdir -p storage/logs 2>/dev/null || true
    {
        echo "Informe de despliegue — $FECHA_INICIO"
        echo "======================================"
        echo ""
        local ok=0 advertencias=0 errores=0
        for paso in "${PASOS[@]}"; do
            IFS='|' read -r estado descripcion detalle <<<"$paso"
            case "$estado" in
                OK) echo "[OK]          $descripcion"; ok=$((ok + 1)) ;;
                ADVERTENCIA)
                    echo "[ADVERTENCIA] $descripcion"
                    [ -n "$detalle" ] && echo "              -> $detalle"
                    advertencias=$((advertencias + 1))
                    ;;
                ERROR)
                    echo "[ERROR]       $descripcion"
                    [ -n "$detalle" ] && echo "              -> $detalle"
                    errores=$((errores + 1))
                    ;;
            esac
        done
        echo ""
        echo "Resumen: $ok correctos, $advertencias advertencias, $errores errores."
        if [ "$errores" -gt 0 ]; then
            echo "Resultado: DESPLIEGUE CON ERRORES."
        elif [ "$advertencias" -gt 0 ]; then
            echo "Resultado: DESPLIEGUE COMPLETADO CON ADVERTENCIAS."
        else
            echo "Resultado: DESPLIEGUE COMPLETADO SIN INCIDENCIAS."
        fi
    } >"$ARCHIVO_INFORME" 2>/dev/null || true
}

# --------------------------------------------------------------------------
# Utilidades de .env
#
# awk con ENVIRON en vez de -v: -v interpreta secuencias de escape
# (\n, \t...) en el valor, y una contraseña tipeada a mano puede traer
# cualquier caracter. ENVIRON no las interpreta.
# --------------------------------------------------------------------------

leer_env() {
    local clave=$1
    [ -f .env ] || return 0
    grep "^${clave}=" .env 2>/dev/null | tail -1 | cut -d'=' -f2-
}

actualizar_env() {
    local clave=$1 valor=$2 tmp
    tmp=$(mktemp)
    if grep -q "^${clave}=" .env 2>/dev/null; then
        CLAVE_AWK="$clave" VALOR_AWK="$valor" awk \
            'BEGIN{FS=OFS="="} $1==ENVIRON["CLAVE_AWK"]{$0=ENVIRON["CLAVE_AWK"] OFS ENVIRON["VALOR_AWK"]} {print}' \
            .env >"$tmp"
        mv "$tmp" .env
    else
        rm -f "$tmp"
        printf '%s=%s\n' "$clave" "$valor" >>.env
    fi
}

generar_clave_aleatoria() {
    tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24
}

# Espera hasta $2 segundos a que todos los servicios de la lista $1 (separados
# por espacio) reporten 'healthy'. Deja el resultado en la variable global
# FALTAN (array) y devuelve 0 si no falta ninguno.
esperar_saludables() {
    local servicios=($1) limite=$2 transcurrido=0 estado s
    FALTAN=()
    while [ "$transcurrido" -lt "$limite" ]; do
        estado=$($DC ps --format '{{.Service}}	{{.Health}}' 2>/dev/null || true)
        FALTAN=()
        for s in "${servicios[@]}"; do
            if ! echo "$estado" | grep -P "^${s}\thealthy$" >/dev/null 2>&1; then
                FALTAN+=("$s")
            fi
        done
        if [ ${#FALTAN[@]} -eq 0 ]; then
            return 0
        fi
        sleep 3
        transcurrido=$((transcurrido + 3))
    done
    return 1
}

echo "═══════════════════════════════════════════════════════════"
echo " Gestor de Tareas y Periodos — despliegue"
echo "═══════════════════════════════════════════════════════════"

# ══════════════════════════════════════════════════════════════
# FASE 1 — Validar el entorno
# ══════════════════════════════════════════════════════════════
fase "Fase 1/6 — Validando el entorno"

if [ ! -f docker-compose.yml ] || [ ! -f artisan ] || [ ! -f composer.json ]; then
    registrar_error "Ubicación del script" "Este script debe ejecutarse desde la raíz del repositorio (falta docker-compose.yml, artisan o composer.json en el directorio actual: $(pwd))."
    escribir_informe
    exit 1
fi
registrar_ok "Ejecutando desde la raíz del repositorio"

if ! command -v docker >/dev/null 2>&1; then
    registrar_error "Docker instalado" "No se encontró el comando 'docker'. Instala Docker Engine (Community Edition o Desktop, cualquiera de los dos sirve) antes de continuar."
    escribir_informe
    exit 1
fi
registrar_ok "Docker está instalado ($(docker --version 2>/dev/null))"

if ! docker info >/dev/null 2>&1; then
    detalle=$(docker info 2>&1 | head -3)
    if echo "$detalle" | grep -qi "permission denied"; then
        registrar_error "Demonio de Docker accesible" "Permiso denegado al hablar con Docker. Añade tu usuario al grupo 'docker' (sudo usermod -aG docker \$USER, luego cierra sesión y vuelve a entrar) o ejecuta este script con sudo."
    else
        registrar_error "Demonio de Docker accesible" "Docker está instalado pero el demonio no responde. ¿Está corriendo el servicio? Detalle: $detalle"
    fi
    escribir_informe
    exit 1
fi
registrar_ok "El demonio de Docker responde"

if ! docker compose version >/dev/null 2>&1; then
    registrar_error "Docker Compose v2 (plugin)" "No se encontró el plugin 'docker compose' (v2). El binario viejo 'docker-compose' (v1, con guion) no sirve para este proyecto. Instala el plugin oficial de Docker Compose."
    escribir_informe
    exit 1
fi
registrar_ok "Docker Compose (plugin v2) disponible ($(docker compose version --short 2>/dev/null))"

if ! command -v curl >/dev/null 2>&1; then
    registrar_error "curl instalado" "No se encontró 'curl', necesario para la validación final. Instálalo (apt install curl / dnf install curl) y vuelve a ejecutar."
    escribir_informe
    exit 1
fi
registrar_ok "curl está disponible"

if command -v make >/dev/null 2>&1; then
    registrar_ok "make está disponible (atajos del día a día: make test, make pint, make respaldo...)"
else
    registrar_advertencia "make no está instalado" "No es necesario para este script, pero sí para el resto del flujo del proyecto (make test, make pint, make respaldo). Instálalo cuando puedas."
fi

if [ ! -f .env.example ]; then
    registrar_error ".env.example presente" "No existe .env.example en la raíz del repositorio. Sin ese archivo no hay valores por defecto de los que partir."
    escribir_informe
    exit 1
fi
registrar_ok ".env.example presente"

# Los puertos ya en uso por ESTE MISMO proyecto (una ejecución anterior) no
# son un conflicto. Solo importa si algo AJENO ya los está usando.
SERVICIOS_YA_CORRIENDO=$($DC ps --status running --services 2>/dev/null || true)
ERRORES_DE_PUERTO=0

for puerto in "${PUERTOS_ESPERADOS[@]}"; do
    if (exec 3<>"/dev/tcp/127.0.0.1/${puerto}") 2>/dev/null; then
        exec 3<&- 3>&-
        if [ -n "$SERVICIOS_YA_CORRIENDO" ]; then
            registrar_advertencia "Puerto $puerto ocupado" "Ya está en uso, probablemente por este mismo proyecto (hay servicios corriendo). Se continúa."
        else
            registrar_error "Puerto $puerto libre" "El puerto $puerto ya está en uso por otro proceso y este proyecto no está corriendo todavía. Libéralo o cambia el puerto en docker-compose.yml y en .env (ver docs/00-entorno.md, sección de puertos)."
            ERRORES_DE_PUERTO=$((ERRORES_DE_PUERTO + 1))
        fi
    else
        registrar_ok "Puerto $puerto libre"
    fi
done

if [ "$ERRORES_DE_PUERTO" -gt 0 ]; then
    escribir_informe
    echo ""
    echo "Hay puertos ocupados por otro proceso que corregir antes de continuar." >&2
    echo "Informe completo en: $ARCHIVO_INFORME" >&2
    exit 1
fi

# ══════════════════════════════════════════════════════════════
# FASE 2 — .env y detección de primera ejecución
# ══════════════════════════════════════════════════════════════
fase "Fase 2/6 — Preparando .env"

PRIMERA_VEZ=false

if [ ! -f .env ]; then
    cp .env.example .env
    registrar_ok "Creado .env a partir de .env.example"
    PRIMERA_VEZ=true
else
    registrar_ok ".env ya existía, se conserva"
fi

APP_KEY_ACTUAL=$(leer_env APP_KEY)
if [ -z "$APP_KEY_ACTUAL" ]; then
    PRIMERA_VEZ=true
fi

if [ "$PRIMERA_VEZ" = true ]; then
    registrar_ok "Detectado: primera vez que se despliega este entorno"
else
    registrar_ok "Detectado: entorno ya inicializado, se actualiza sin tocar los datos"
fi

# UID/GID del host: no es un secreto, se ajusta siempre sin preguntar. Sin
# esto, lo que los contenedores escriben en el bind mount queda como root.
UID_HOST=$(id -u)
GID_HOST=$(id -g)
actualizar_env "UID" "$UID_HOST"
actualizar_env "GID" "$GID_HOST"
registrar_ok "UID/GID del host alineados con el contenedor ($UID_HOST:$GID_HOST)"

# ══════════════════════════════════════════════════════════════
# FASE 3 — Credenciales de la base de datos
# ══════════════════════════════════════════════════════════════
fase "Fase 3/6 — Credenciales de la base de datos"

# Filtrado por proyecto, no solo por el nombre corto del volumen: dos
# proyectos de Compose distintos en la misma máquina pueden llamar los dos
# a su volumen "datos-mysql" sin chocar entre sí (Compose los prefija con
# el nombre del proyecto). Sin el filtro de proyecto, el volumen de OTRO
# despliegue de este mismo repo en otra carpeta dispara una advertencia que
# no aplica aquí.
PROYECTO_COMPOSE=$(docker compose config --format json 2>/dev/null | grep -m1 '"name"' | sed -E 's/.*"name":[[:space:]]*"([^"]*)".*/\1/')
VOLUMEN_MYSQL_EXISTE=$(docker volume ls \
    --filter label=com.docker.compose.volume=datos-mysql \
    --filter label=com.docker.compose.project="$PROYECTO_COMPOSE" \
    -q 2>/dev/null || true)

CREDENCIALES_GENERADAS=()

pedir_clave() {
    local etiqueta=$1 nombre_var=$2 valor_actual
    valor_actual=$(leer_env "$nombre_var")

    if [ -n "$valor_actual" ]; then
        registrar_ok "$etiqueta ya estaba configurada en .env; se conserva"
        return 0
    fi

    if [ -n "$VOLUMEN_MYSQL_EXISTE" ]; then
        echo ""
        echo "  ⚠️  Ya existe un volumen de base de datos de una instalación anterior"
        echo "     (datos-mysql), pero $nombre_var está vacía en .env."
        echo "     Si esa base ya tiene datos reales, escribe aquí la contraseña"
        echo "     ORIGINAL con la que se creó — una nueva no va a coincidir y la"
        echo "     aplicación no podrá conectarse a MySQL."
        echo ""
    fi

    if [ "$INTERACTIVO" != true ]; then
        local generada
        generada=$(generar_clave_aleatoria)
        actualizar_env "$nombre_var" "$generada"
        CREDENCIALES_GENERADAS+=("$nombre_var=$generada")
        registrar_advertencia "$etiqueta generada automáticamente (sin terminal interactiva)" "Guardada en .env. Revísala con: grep $nombre_var .env"
        return 0
    fi

    local intento=1 clave1 clave2
    while [ "$intento" -le 3 ]; do
        read -rsp "  $etiqueta (Enter para generar una aleatoria): " clave1
        echo
        if [ -z "$clave1" ]; then
            clave1=$(generar_clave_aleatoria)
            echo "  Generada automáticamente: $clave1"
            actualizar_env "$nombre_var" "$clave1"
            CREDENCIALES_GENERADAS+=("$nombre_var=$clave1")
            registrar_ok "$etiqueta generada automáticamente"
            return 0
        fi
        read -rsp "  Repite $etiqueta para confirmar: " clave2
        echo
        if [ "$clave1" = "$clave2" ]; then
            actualizar_env "$nombre_var" "$clave1"
            registrar_ok "$etiqueta configurada"
            return 0
        fi
        echo "  Las dos claves no coinciden. Intento $intento de 3."
        intento=$((intento + 1))
    done

    registrar_error "$etiqueta" "No se logró confirmar la clave en 3 intentos."
    return 1
}

ERRORES_DE_CLAVE=0
pedir_clave "Contraseña de la base de datos (usuario de la aplicación)" DB_PASSWORD || ERRORES_DE_CLAVE=$((ERRORES_DE_CLAVE + 1))
pedir_clave "Contraseña de root de MySQL" DB_ROOT_PASSWORD || ERRORES_DE_CLAVE=$((ERRORES_DE_CLAVE + 1))

if [ "$ERRORES_DE_CLAVE" -gt 0 ]; then
    abortar
fi

if [ ${#CREDENCIALES_GENERADAS[@]} -gt 0 ]; then
    echo ""
    echo "  Se generaron automáticamente estas credenciales — guárdalas en un"
    echo "  gestor de contraseñas, no viajan en ningún otro sitio:"
    for c in "${CREDENCIALES_GENERADAS[@]}"; do
        echo "    $c"
    done
fi

# ══════════════════════════════════════════════════════════════
# FASE 4 — Construir y levantar
# ══════════════════════════════════════════════════════════════
fase "Fase 4/6 — Construyendo y levantando los servicios"

ejecutar "Imágenes construidas (app, worker, node)" $DC build || abortar

ejecutar "Servicios levantados en segundo plano" $DC up -d || abortar

# El Dockerfile de app/worker instala el binario de Composer pero no corre
# `install`: en este entorno de referencia vendor/ ya existía desde antes de
# dockerizar, y ese hueco quedó invisible. En un clon de verdad, sin
# vendor/, artisan no arranca ("Failed to open .../autoload.php") y node
# —que también necesita artisan, para wayfinder:generate— entra en bucle
# de reinicio. Va antes de esperar "healthy": si no, esa espera nunca
# termina para ese servicio.
#
# Pequeño reintento: justo tras `up -d`, el proceso de PHP-FPM dentro de
# "app" puede tardar un instante en aceptar `exec`.
intento_composer=1
until $DC exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader >/tmp/desplegar_composer.log 2>&1; do
    if [ "$intento_composer" -ge 5 ]; then
        registrar_error "Dependencias de PHP instaladas (composer install)" "$(cat /tmp/desplegar_composer.log 2>/dev/null)"
        abortar
    fi
    sleep 3
    intento_composer=$((intento_composer + 1))
done
registrar_ok "Dependencias de PHP instaladas (composer install)"
rm -f /tmp/desplegar_composer.log

# node pudo quedar en bucle de reinicio si vendor/ no existía todavía
# cuando arrancó por primera vez; con vendor/ ya en su sitio, un reinicio
# explícito es más rápido que esperar el backoff automático de Docker.
#
# worker NO se reinicia aquí a propósito: además de vendor/, necesita las
# tablas de la base de datos (queue:work consulta la tabla `cache`), que
# todavía no existen — eso se resuelve después de migrar, en la Fase 5.
# Por eso worker queda fuera de la espera de "healthy" de esta fase: en una
# base de datos nueva nunca la pasaría, sin importar cuánto se espere.
$DC restart node >/dev/null 2>&1 || true

echo "  Esperando a que los servicios (sin worker, que necesita la base migrada) queden saludables (hasta 2 minutos)..."
if esperar_saludables "app nginx mysql node" 120; then
    registrar_ok "app, nginx, mysql y node están 'healthy'"
else
    detalle=$($DC ps 2>&1)
    registrar_error "Servicios saludables" "Tras 120s, no quedaron 'healthy': ${FALTAN[*]}. Estado actual:
$detalle"
    abortar
fi

# ══════════════════════════════════════════════════════════════
# FASE 5 — Preparar la aplicación
# ══════════════════════════════════════════════════════════════
fase "Fase 5/6 — Preparando la aplicación"

APP_KEY_ACTUAL=$(leer_env APP_KEY)
if [ -z "$APP_KEY_ACTUAL" ]; then
    ejecutar "Clave de aplicación generada (APP_KEY)" \
        $DC exec -T app php artisan key:generate --force --ansi || abortar
else
    registrar_ok "APP_KEY ya estaba configurada; se conserva"
fi

if [ "$PRIMERA_VEZ" = true ]; then
    CARGAR_SEEDERS=false
    if [ "$INTERACTIVO" = true ]; then
        echo ""
        read -rp "  ¿Cargar datos de ejemplo (clientes, proyectos y usuarios de prueba)? [s/N]: " respuesta
        case "$respuesta" in
            [sS]*) CARGAR_SEEDERS=true ;;
        esac
    else
        registrar_advertencia "Sin terminal interactiva: se omiten los datos de ejemplo" "Ejecuta 'docker compose exec app php artisan db:seed' más tarde si los necesitas."
    fi

    if [ "$CARGAR_SEEDERS" = true ]; then
        ejecutar "Base de datos migrada y con datos de ejemplo" \
            $DC exec -T app php artisan migrate --seed --force || abortar
        SE_CARGARON_SEEDERS=true
    else
        ejecutar "Base de datos migrada (sin datos de ejemplo)" \
            $DC exec -T app php artisan migrate --force || abortar
        SE_CARGARON_SEEDERS=false
    fi
else
    ejecutar "Migraciones pendientes aplicadas" \
        $DC exec -T app php artisan migrate --force || abortar
    SE_CARGARON_SEEDERS=false
fi

ejecutar "Disco público de archivos enlazado (storage:link)" \
    $DC exec -T app php artisan storage:link
# storage:link no es crítico: si ya existía, Laravel lo informa y no es un
# fallo real. No se aborta el despliegue por esto (sin `|| abortar`).

if [ -d .git ]; then
    if cp docker/git-hooks/pre-commit .git/hooks/pre-commit 2>/dev/null && chmod +x .git/hooks/pre-commit; then
        registrar_ok "Hook de git (pre-commit, formato con Pint) instalado"
    else
        registrar_advertencia "No se pudo instalar el hook de git" "Ejecuta 'make hooks' manualmente cuando puedas."
    fi
else
    registrar_advertencia "No hay repositorio .git en este directorio" "Se omite la instalación del hook de pre-commit."
fi

# Las tablas ya existen: ahora sí puede quedar sano. Un reinicio explícito
# es más rápido que esperar el backoff automático de Docker.
$DC restart worker >/dev/null 2>&1 || true
echo "  Esperando a que 'worker' quede saludable ahora que la base ya está migrada (hasta 30s)..."
if esperar_saludables "worker" 30; then
    registrar_ok "worker está 'healthy'"
else
    detalle=$($DC logs worker --tail=15 2>&1)
    registrar_error "worker saludable" "Sigue sin quedar 'healthy' tras migrar. Últimas líneas de su log:
$detalle"
fi

# ══════════════════════════════════════════════════════════════
# FASE 6 — Validación final
# ══════════════════════════════════════════════════════════════
fase "Fase 6/6 — Validando que todo funciona"

estado_final=$($DC ps --format '{{.Service}}	{{.Health}}' 2>/dev/null || true)
todos_sanos=true
for s in app nginx mysql node worker; do
    if ! echo "$estado_final" | grep -P "^${s}\thealthy$" >/dev/null 2>&1; then
        todos_sanos=false
    fi
done
if [ "$todos_sanos" = true ]; then
    registrar_ok "Los cinco servicios siguen 'healthy'"
else
    registrar_error "Servicios saludables al final" "Alguno no está 'healthy'. Estado: $estado_final"
fi

PUERTO_WEB=$($DC port nginx 80 2>/dev/null | cut -d: -f2)
if [ -n "$PUERTO_WEB" ]; then
    if respuesta_http=$(curl -fsS -o /dev/null -w '%{http_code}' "http://localhost:${PUERTO_WEB}/up" 2>&1); then
        if [ "$respuesta_http" = "200" ]; then
            registrar_ok "La aplicación responde en http://localhost:${PUERTO_WEB} (HTTP 200)"
        else
            registrar_error "Respuesta de la aplicación" "http://localhost:${PUERTO_WEB}/up devolvió HTTP $respuesta_http, se esperaba 200."
        fi
    else
        registrar_error "Respuesta de la aplicación" "No se pudo conectar a http://localhost:${PUERTO_WEB}/up. Detalle: $respuesta_http"
    fi
else
    registrar_advertencia "No se pudo determinar el puerto publicado de nginx" "Verifica manualmente en el navegador."
fi

pendientes=$($DC exec -T app php artisan migrate:status --no-ansi 2>&1 | grep -c "Pending" || true)
if [ "$pendientes" = "0" ]; then
    registrar_ok "No hay migraciones pendientes"
else
    registrar_error "Migraciones pendientes" "Quedaron $pendientes migraciones sin aplicar. Ejecuta 'docker compose exec app php artisan migrate' y revisa el error."
fi

resultado_login=$($DC exec -T app php artisan tinker --execute="
echo \Illuminate\Support\Facades\Auth::once(['email' => '${CORREO_PRUEBA}', 'password' => '${CLAVE_PRUEBA}']) ? 'LOGIN_OK' : 'LOGIN_FALLO';
" 2>&1)

if echo "$resultado_login" | grep -q "LOGIN_OK"; then
    registrar_ok "Inicio de sesión verificado con el usuario de prueba ($CORREO_PRUEBA)"
elif [ "${SE_CARGARON_SEEDERS:-false}" = true ]; then
    registrar_error "Inicio de sesión" "Se cargaron los seeders pero no se pudo iniciar sesión con $CORREO_PRUEBA. Detalle: $resultado_login"
else
    registrar_advertencia "No se verificó el inicio de sesión" "No se cargaron datos de ejemplo en este despliegue, así que no hay un usuario de prueba conocido para probar. Esto es normal en un entorno sin seeders."
fi

# ══════════════════════════════════════════════════════════════
# Informe final
# ══════════════════════════════════════════════════════════════
escribir_informe

echo ""
echo "═══════════════════════════════════════════════════════════"
echo " Informe final"
echo "═══════════════════════════════════════════════════════════"
cat "$ARCHIVO_INFORME"
echo ""
echo "Guardado en: $ARCHIVO_INFORME"

HUBO_ERROR=false
for paso in "${PASOS[@]}"; do
    case "$paso" in
        ERROR\|*) HUBO_ERROR=true ;;
    esac
done

if [ "$HUBO_ERROR" = true ]; then
    exit 1
fi

exit 0
