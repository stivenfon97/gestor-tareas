# Control de versiones

El proyecto está bajo Git desde el cierre de la fase 06. Este documento fija qué se
versiona, cómo se nombra un commit, cómo se organiza una fase en ramas y cómo se
deshace una fase entera si sale mal.

---

## El punto de partida

**Las fases 01 a 06 no tienen historial.** Se construyeron sin control de versiones y
reconstruirlo a posteriori habría producido commits inventados: fechas falsas, diffs
que nunca existieron y mensajes escritos desde el resultado. Un historial ficticio es
peor que no tenerlo, porque invita a confiar en él.

Lo que sí se conserva es el razonamiento, que es lo que de verdad se pierde: está en
`docs/` —sobre todo en las secciones **Decisiones de diseño** de cada módulo— y en los
resúmenes de cierre de cada fase.

El commit inicial representa, en una sola pieza, *el estado del proyecto al terminar la
fase 06*, y está etiquetado `fase-06`. A partir de ahí cada fase deja su rastro.

---

## Qué se versiona y qué no

### Sí se versiona, aunque sorprenda

| Ruta | Por qué |
|---|---|
| `.ai/rules/` | Son decisiones del proyecto atadas a rutas, no configuración personal. Se comparten. |
| `.ai/guidelines/` | El glosario y las convenciones. Misma razón. |
| `.ai/skills/` | Los cuatro procedimientos de dominio. Es la **fuente de verdad** de las skills. |
| `docs/` | Entera. Es el porqué del proyecto. |
| `docker/`, `docker-compose.yml`, `Makefile` | El entorno es parte del proyecto: sin ellos el repositorio no arranca. |
| `stubs/` | Plantillas publicadas de `maatwebsite/excel`, ya modificadas. |
| `.github/` | El workflow de pruebas y la configuración de Dependabot. |

### Nunca se versiona

| Ruta | Por qué |
|---|---|
| `.env` | Credenciales reales. `.env.example` sí, con los valores sensibles vacíos. |
| `/vendor`, `/node_modules` | Se reconstruyen desde `composer.lock` y `package-lock.json`. |
| `/storage/app/public`, `/storage/framework`, `/storage/logs`, `/storage/pail` | Datos de ejecución y caché. Solo viajan los `.gitignore` que sostienen los directorios. |
| `/public/build`, `/public/hot`, `/public/storage` | Artefactos de compilación y un enlace simbólico. |
| `.mcp.json`, `CLAUDE.md`, `AGENTS.md`, `boost.json` | Los regenera Laravel Boost con `php artisan boost:install`. Versionarlos produce conflictos en cada regeneración. |
| `/resources/js/actions`, `/resources/js/routes`, `/resources/js/wayfinder` | Los genera Wayfinder desde las rutas de PHP. El código de PHP es la fuente. |
| `/lang/php_*.json` | Traducciones compiladas por `laravel-vue-i18n` desde los `.php` de `lang/`. |
| `/.claude/settings.local.json`, `/.claude/skills` | Configuración local del agente. Las skills de dominio son enlaces simbólicos a `.ai/skills/`, que sí se versiona; las de framework las reinstala Boost. |
| `.phpunit.result.cache`, `/.phpunit.cache`, `/auth.json` | Caché de pruebas y credenciales de Composer. |

> **La regla de fondo:** se versiona lo que un humano escribió y lo que no se puede
> regenerar. Todo lo derivable de otra cosa que sí está en el repositorio, fuera.

### Cómo comprobar que el `.gitignore` funciona

No basta con escribirlo. Antes de cualquier commit grande:

```bash
git status --short | grep -E '\.env$|node_modules|/vendor/|auth\.json'   # debe salir vacío
git status --short --ignored | grep '^!!' | grep -E '\.ai/|docs/|docker/' # debe salir vacío
```

El primero verifica que nada sensible entra; el segundo, que nada importante se queda
fuera por accidente. Un `.env` dentro del historial no se borra con un commit: hay que
reescribir el historial entero.

---

## Ramas

**Una rama por fase, partiendo siempre de `main`:**

```
fase/07-tabla-tanstack
fase/08-kanban
fase/09-periodos-pagos-trm
```

Trabajar directamente en `main` funcionaría siendo una sola persona, pero la rama por
fase da algo concreto: si una fase sale mal, `git switch main` devuelve el proyecto al
estado anterior sin deshacer nada a mano.

Al cerrar la fase se fusiona **siempre con `--no-ff`** y se etiqueta:

```bash
git switch main
git merge --no-ff fase/07-tabla-tanstack -m "merge: fase 07 — tabla TanStack e identidad visual"
git tag -a fase-07 -m "Componente de tabla e identidad visual"
```

`--no-ff` no es cosmético. Fuerza un nodo de merge aunque el avance sea lineal, y ese
nodo es lo que convierte "la fase 07" en un objeto que se puede revertir de un solo
tirón. Con un fast-forward, la fase se disuelve en commits sueltos y revertirla obliga
a identificarlos uno por uno.

**La suite tiene que estar verde antes del merge**, no después.

---

## Commits

**Conventional Commits, con el cuerpo en español.**

```
feat: componente de tabla con TanStack
fix: el filtro de módulo omitía los descendientes de tercer nivel
refactor: extraer la resolución de rol a un servicio
test: cubrir las transiciones inválidas por HTTP
docs: documentar el módulo de tareas
chore: fijar TanStack Table a ^8
```

Prefijos en uso: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `style`, `perf`.

Reglas:

- **Un commit por unidad coherente.** Ni uno por fase ni uno por archivo. Una migración
  con su modelo, su factory y su prueba es *una* unidad. Diez componentes sin relación
  entre sí, no.
- **Nunca se commitea con la suite en rojo.** Si un commit intermedio rompe pruebas, se
  arregla antes de commitear, no en el commit siguiente. Un historial con commits rotos
  hace inútil `git bisect`, que es justo para lo que sirve el historial.
- **El cuerpo explica el porqué, no el qué.** El diff ya dice qué cambió.
- Si el commit implementa una decisión de un prompt de fase o corrige un hallazgo de un
  resumen de cierre, dilo en el cuerpo. Es lo que ata el código a su razón.

---

## El hook `pre-commit`

Verifica el formato con Pint y aborta el commit si falla.

```bash
make hooks
```

Hace falta ejecutarlo **una vez por clon**: Git no versiona `.git/hooks`, así que el
hook vive en `docker/git-hooks/pre-commit` —eso sí versionado— y `make hooks` lo copia.

**Solo corre Pint, nunca la suite completa.** Un hook que tarda un minuto en cada commit
se acaba esquivando con `--no-verify`, y un hook que se esquiva es un hook que no
existe. La suite se ejecuta antes de fusionar la fase, que es donde importa.

El hook ejecuta Pint dentro del contenedor `app`, así que necesita el stack levantado.
Si `app` no está corriendo, aborta con un mensaje explícito en vez de fallar de forma
confusa.

---

## Cómo revertir

### Una fase entera

Gracias al merge `--no-ff`, la fase es un solo nodo:

```bash
git log --oneline --merges                    # localizar el commit de merge
git revert -m 1 <sha-del-merge>               # -m 1 = conservar la línea de main
```

`git revert` crea un commit nuevo que deshace los cambios y **no reescribe historial**,
que es lo que lo hace seguro. El rastro de que la fase existió y se revirtió queda a la
vista, y volver a aplicarla luego es revertir el revert.

### Volver a mirar el estado de una fase, sin deshacer nada

```bash
git switch --detach fase-09      # el proyecto tal y como quedó al cerrar la fase 09
git switch -                     # de vuelta a donde estabas
```

### Un solo commit

```bash
git revert <sha>
```

---

## Qué añade cada fase a partir de la 07

Al cierre de una fase, además de sus criterios de aceptación:

- [ ] Los commits de la fase, fusionados a `main` con `--no-ff`.
- [ ] La etiqueta `fase-NN`.
- [ ] En el resumen de cierre, el listado de commits: `git log --oneline main..fase/NN`.

---

## Remoto

El repositorio **no tiene remoto configurado**. Si se añade uno:

> ### ⚠️ Tiene que ser privado
>
> Este repositorio contiene nombres de clientes reales, montos de pagos y tasas de
> cambio. Un repositorio público expone información comercial de terceros que no dieron
> permiso para publicarla.

```bash
git remote add origin <URL-del-repositorio-privado>
git push -u origin main
git push --tags
```

**El remoto no es una copia de seguridad de los archivos.** `storage/app` está ignorado
y los adjuntos viven en el volumen `archivos-storage` de Docker: no viajan al remoto.
Respaldar los archivos subidos es un problema aparte, y sigue sin resolver.
