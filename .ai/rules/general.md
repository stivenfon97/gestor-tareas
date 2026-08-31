---
paths:
  - docker-compose.yml
  - '.env*'
  - .gitignore
  - '**'
---

# General

## Orden de los mounts: archivos-storage va después del bind mount del código
El volumen nombrado `archivos-storage` debe montarse DESPUÉS del bind mount del código (`.:/var/www/html`), o `storage/app` queda sobrescrito y los archivos subidos se pierden al reiniciar:

```yaml
volumes:
  - .:/var/www/html                              # primero el código
  - archivos-storage:/var/www/html/storage/app   # después el volumen
```

Aplica igual a `app` y a `worker`, que comparten ese volumen.

Otros dos detalles del mismo archivo, verificados a base de fallos:
- Los healthchecks deben apuntar a `127.0.0.1`, no a `localhost`. En Alpine `localhost` resuelve primero a `::1` y nginx solo escucha en IPv4, así que el healthcheck falla con "Connection refused" aunque el servicio esté sano.
- `app` y `worker` corren con `user: "${UID:-1000}:${GID:-1000}"`. Sin eso, lo que los contenedores escriben en el bind mount queda como root y el host no lo puede editar.

## DB_HOST es "mysql" y no se usa Redis
`DB_HOST` es `mysql`, el nombre del servicio de Compose, nunca `127.0.0.1`. Dentro de un contenedor `127.0.0.1` es el propio contenedor, y el fallo que produce ("Connection refused") no señala la causa.

`mysqld` escucha en **3308** (no en 3306): el servicio lo arranca con `command: ["--port=3308"]` para que este proyecto no comparta puerto ni servidor con el otro stack de MySQL de la máquina, que ya ocupa el 3307 del host. De ahí salen dos números que no hay que confundir:

- `DB_PORT=3308` describe el puerto **interno**, el que usa Laravel desde la red de Compose.
- **3309** es el puerto publicado en el host (`"3309:3308"`), solo para clientes externos como DBeaver.

Si cambias `--port`, cambia con él `DB_PORT`, el lado derecho del mapeo de `ports` y el `-P` del healthcheck de `mysqladmin`: los cuatro describen el mismo puerto y desalinearlos deja el contenedor `unhealthy` sin decir por qué.

Las colas, la caché y las sesiones usan el driver `database` a propósito: este proyecto no usa Redis ni Horizon. No añadas variables `REDIS_*` ni cambies estos drivers.

## Qué se versiona y qué no
`.ai/`, `docs/`, `docker/` y `prompts/` se versionan siempre: son decisiones y razonamiento del proyecto, no configuración personal. `.ai/skills/` es la fuente de verdad de las skills; `.claude/skills/` solo contiene enlaces simbólicos a ella.

Nunca se versionan: `.env` (jamás; `.env.example` sí, con los valores sensibles vacíos), `.mcp.json`, `CLAUDE.md`, `AGENTS.md` y `boost.json` (los regenera `php artisan boost:install` y versionarlos produce conflictos en cada regeneración), los artefactos de Wayfinder en `resources/js/{actions,routes,wayfinder}`, `lang/php_*.json` (compilados por laravel-vue-i18n), `vendor/`, `node_modules/`, `storage/` y `public/build`.

Regla de fondo: se versiona lo escrito por un humano y lo no regenerable. Antes de un commit grande, verifica los dos lados:
`git status --short | grep -E '\.env$|node_modules|/vendor/|auth\.json'` debe salir vacío, y
`git status --short --ignored | grep '^!!' | grep -E '\.ai/|docs/|docker/'` también.
Un `.env` dentro del historial no se borra con un commit: obliga a reescribirlo entero.

## Disciplina de commits
**Nunca se commitea con la suite en rojo.** Si un commit intermedio rompe pruebas, se arregla antes de commitear, no en el commit siguiente: un historial con commits rotos inutiliza `git bisect`, que es para lo que sirve el historial.

Un commit por unidad coherente: ni uno por fase ni uno por archivo. Una migración con su modelo, su factory y su prueba es una unidad; diez componentes sin relación, no.

Mensaje en Conventional Commits (`feat`, `fix`, `refactor`, `test`, `docs`, `chore`) con el cuerpo en español explicando el **porqué**, no el qué: el diff ya dice qué cambió. Si el commit implementa una decisión de un prompt de fase o corrige un hallazgo de un resumen de cierre, dilo en el cuerpo.

Una rama por fase (`fase/NN-descripcion`), fusionada a `main` con `--no-ff` y etiquetada `fase-NN`. El `--no-ff` no es cosmético: el nodo de merge es lo que permite revertir una fase entera con `git revert -m 1 <sha>`.

El hook `pre-commit` (Pint) se instala con `make hooks`, una vez por clon. Detalle completo en `docs/04-control-de-versiones.md`.
