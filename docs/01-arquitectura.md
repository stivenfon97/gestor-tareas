# Arquitectura

Cómo está construida la aplicación y por qué. Para la infraestructura —Docker, puertos,
volúmenes— ver [`00-entorno.md`](00-entorno.md).

## Visión general

Monolito Laravel con frontend Vue servido por Inertia: un solo despliegue, sin API
pública ni cliente separado.

```
Navegador
   │  petición HTTP
   ▼
routes/web.php ──► middleware (auth, verified, AsegurarUsuarioActivo, can:…)
   │
   ▼
Controlador  ──► Gate::authorize(…)  ──►  Policy  ──►  ResolverRolProyecto
   │                                                        │
   │                                                   proyecto_usuario
   ▼
Acción / modelo (+ Observer)  ──►  base de datos
   │
   ▼
Inertia::render('pagina', props)  ──►  componente Vue
```

Las capas, de fuera adentro:

| Capa | Dónde | Qué le toca |
|---|---|---|
| Rutas | `routes/web.php` | Nombrar la URL y poner el middleware grueso |
| Controladores | `app/Http/Controllers/` | Autorizar, validar, delegar, responder. **Sin lógica de negocio** |
| Form requests | `app/Http/Requests/` | Validación |
| Policies | `app/Policies/` | Quién puede hacer qué |
| Servicios y acciones | `app/Services/`, `app/Actions/` | Operaciones con varias piezas |
| Modelos y observers | `app/Models/`, `app/Observers/` | Estado, relaciones e invariantes que no pueden depender de que alguien se acuerde |
| Enums | `app/Enums/` | Los conjuntos cerrados del dominio y su comportamiento |
| Vistas | `resources/js/pages/` | Presentación. Ningún texto ni color literal |

## Decisiones y su porqué

### Inertia en lugar de una API REST + SPA

No hay clientes externos que consuman datos. Una API separada añadiría autenticación por
token, versionado y serialización a cambio de nada. Con Inertia, la autorización se
resuelve una sola vez en el servidor y el componente Vue recibe ya lo que puede ver.

**Consecuencia que hay que respetar:** lo que se manda como prop es lo que el navegador
recibe. Filtrar en el componente no filtra nada.

### Las invariantes viven en observers, no en controladores

El código de tarea y el historial de estados los escribe `TareaObserver`; `monto_cop` lo
calcula `PagoObserver`. Si dependieran de que el controlador se acuerde de llamarlos,
algún camino —un comando de consola, una acción masiva, un seeder— no llamaría, y el dato
se perdería en silencio.

### Enums de PHP en lugar de tablas de catálogo

Los conjuntos cerrados (estados, tipos, roles, visibilidad) son enums con comportamiento:
`transicionesPermitidas()`, `prioridadPorDefecto()`, `escribeTrabajo()`. Una tabla de
catálogo obligaría a un join para responder preguntas que son de código, no de datos.

Las columnas se guardan como `varchar`, no como `ENUM` de MySQL: alterar un `ENUM`
reconstruye la tabla, y `historial_estados` debe poder guardar valores que el enum deje de
tener.

## Autorización

La decisión estructural de la fase 04. El detalle completo está en
[`modulos/autenticacion.md`](modulos/autenticacion.md) y la matriz en
[`diagramas/permisos-y-roles.md`](diagramas/permisos-y-roles.md).

### Dónde vive cada decisión

| Pregunta | Se responde en |
|---|---|
| ¿Está autenticado? | Middleware `auth` (Fortify) |
| ¿Sigue activo? | Middleware `AsegurarUsuarioActivo` |
| ¿Tiene el permiso global? | `spatie/laravel-permission`, middleware `can:…` o `$user->can()` en la policy |
| ¿Qué rol tiene en **este** proyecto? | `ResolverRolProyecto`, leyendo `proyecto_usuario` |
| ¿Puede hacer **esta acción** sobre **este objeto**? | La policy del modelo |
| ¿Qué filas puede ver en un **listado**? | El scope `visiblesPara()` del modelo |
| ¿Se pinta el botón? | `usePermisos()` en Vue — **y solo eso** |

`Gate::before` existe una sola vez y solo para `administrador`. Cualquier otro atajo
convierte las policies en decorado, y hay una prueba que lo comprueba.

### Por qué la visibilidad se aplica dos veces

Una policy contesta sobre **un objeto**: `¿puede ver esta reunión?`. Un listado no pasa
por ahí — devuelve un conjunto, y si el conjunto no se acota, el título de la reunión
interna llega al navegador aunque abrir su detalle dé 403. *"Comité interno de márgenes"*
ya es información filtrada.

De ahí el reparto:

- **Policy** → acceso por id (`GET /reuniones/{id}`).
- **Scope `visiblesPara(User)`** → todo listado (`GET /proyectos/{id}/reuniones`).

Los dos son obligatorios. Ninguno sustituye al otro, y la regla está registrada en
`.ai/rules/` para los globs de `Reunion` y `NotaTarea`.

### Por qué los archivos no tienen URL directa

Un enlace a `storage/…` no pregunta quién lo abre. Todo adjunto se sirve por
`archivos.descargar`, que evalúa `MediaPolicy` antes de emitir un byte. Los respaldos
`.sql` viven además en **disco privado**, de modo que la URL directa ni siquiera existe.

### Por qué el frontend no decide nada

`usePermisos()` sirve para no enseñar puertas cerradas. Si el composable y una policy
discrepan, manda la policy y lo que hay en el composable es un bug de presentación —no un
agujero—. Ese reparto es lo que permite cambiar la interfaz sin auditar la seguridad otra
vez.

## Convenciones transversales

Están registradas en `.ai/rules/`, que es lo que un agente carga al editar cada archivo.
Las que más se notan:

| Regla | Glob |
|---|---|
| Dinero en `decimal`, `onDelete` explícito | `database/migrations/**` |
| `strict_types`, casts tipados, relaciones con tipo de retorno | `app/Models/**` |
| Enums con `etiqueta()` y `token()`, nunca colores | `app/Enums/**` |
| Controladores delgados, listados por query-builder | `app/Http/Controllers/**` |
| El rol se resuelve desde el pivote; gana el más restrictivo | `app/Policies/**` |
| Ningún texto visible dentro del componente | `resources/js/**` |
| Ningún color literal | `resources/js/components/**` |
| Toda clave de español existe también en inglés | `lang/**` |

Ver [`README.md`](README.md#dónde-vive-cada-cosa) para el reparto entre `.ai/` y `docs/`.
