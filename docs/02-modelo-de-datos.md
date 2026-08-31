# Modelo de datos

Diecisiete tablas de dominio, creadas en la fase 03. A ellas se suman `users`
(que Laravel impone), `media` (Medialibrary) y las tablas de
`spatie/laravel-permission`, que este documento no describe.

## Diagrama entidad-relación

Ver [`diagramas/modelo-entidad-relacion.md`](diagramas/modelo-entidad-relacion.md),
que incluye además el comportamiento de cada `onDelete`.

## Convenciones aplicadas

- **Dinero en `decimal`, nunca `float`.** Montos `decimal(12,2)`, tasas de cambio
  `decimal(12,4)`, y `monto_cop` `decimal(14,2)` porque un millón de pesos por cada
  doscientos cincuenta dólares desborda antes la parte entera.
- **Toda clave foránea declara su `onDelete`.** Qué pasa al borrar un cliente con
  proyectos es una decisión de negocio, no un detalle del motor.
- **Los enums se guardan como `varchar(20)`, no como `ENUM` de MySQL.** El conjunto
  cerrado vive en PHP (`app/Enums/`); alterar un `ENUM` de MySQL obliga a reconstruir
  la tabla. Además `historial_estados` guarda estados que pueden dejar de existir en el
  enum: un registro histórico no puede quedar inválido porque el dominio evolucione.
- **Nombres en español**, salvo `users`, `password`, `created_at` y demás que Laravel
  impone. La columna de usuario en las tablas propias es `usuario_id`, no `user_id`.

---

## Diccionario de datos

### `clientes`

Empresa o persona que contrata.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `id` | `bigint unsigned` | no | |
| `nombre` | `varchar(255)` | no | Razón social |
| `contacto_nombre` | `varchar(255)` | sí | Persona con la que se habla |
| `contacto_email` | `varchar(255)` | sí | |
| `contacto_telefono` | `varchar(255)` | sí | |
| `notas` | `text` | sí | Contexto libre |
| `activo` | `tinyint(1)` | no | Por defecto `1`. Un cliente inactivo se conserva |
| `created_at` / `updated_at` | `timestamp` | sí | |

### `proyectos`

Una aplicación o sistema. Tiene módulos, tareas y periodos propios.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `id` | `bigint unsigned` | no | |
| `cliente_id` | `bigint unsigned` | no | → `clientes` · **restrict** |
| `nombre` | `varchar(255)` | no | |
| `slug` | `varchar(255)` | no | **unique**. Identificador en la URL |
| `prefijo_tarea` | `varchar(10)` | no | **unique**, mayúsculas. Alimenta el código: `POR-0001` |
| `descripcion` | `text` | sí | |
| `estado` | `varchar(20)` | no | `EstadoProyecto`, por defecto `activo` |
| `deleted_at` | `timestamp` | sí | Borrado suave |

### `modulos`

División funcional de un proyecto. Autoreferencial.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `proyecto_id` | `bigint unsigned` | no | → `proyectos` · **cascade** |
| `parent_id` | `bigint unsigned` | sí | → `modulos` · **cascade**. Nulo = módulo raíz |
| `nombre` | `varchar(255)` | no | |
| `descripcion` | `text` | sí | |
| `orden` | `int` | no | Por defecto `0`. Orden manual entre hermanos |

Índice compuesto en `(proyecto_id, parent_id, orden)`: es exactamente como se consulta
el árbol.

### `equipos` y `equipo_usuario`

Agrupación de usuarios. **Es un atajo de asignación masiva**: el rol efectivo sobre un
proyecto se resuelve siempre desde `proyecto_usuario`, nunca desde el equipo.

`equipo_usuario` es `(equipo_id → equipos cascade, usuario_id → users cascade)` con
**unique compuesto**. No lleva timestamps: pertenecer o no a un equipo no tiene
historia que guardar.

### `proyecto_usuario`

Quién participa en qué proyecto y con qué rol.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `proyecto_id` | `bigint unsigned` | no | → `proyectos` · **cascade** |
| `usuario_id` | `bigint unsigned` | no | → `users` · **cascade** |
| `rol` | `varchar(20)` | no | `RolProyecto`: `lider`, `colaborador`, `observador`, `cliente` |

**Unique en `(proyecto_id, usuario_id)`**: un usuario tiene un solo rol por proyecto, y
lo garantiza la base de datos, no solo la validación.

### `periodos`

Bloque de trabajo facturable de un proyecto.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `proyecto_id` | `bigint unsigned` | no | → `proyectos` · **cascade** |
| `consecutivo` | `int` | no | Número dentro del proyecto |
| `fecha_inicio` | `date` | no | |
| `fecha_fin` | `date` | **sí** | Se llena al cerrar |
| `estado` | `varchar(20)` | no | `EstadoPeriodo`, por defecto `abierto` |
| `notas` | `text` | sí | |

**Unique en `(proyecto_id, consecutivo)`.** Los periodos son **por proyecto**, no por
cliente.

### `pagos`

El cobro de un periodo.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `periodo_id` | `bigint unsigned` | no | → `periodos` · **cascade** · **UNIQUE** |
| `fecha_pago` | `date` | no | Fecha a la que se consulta la TRM |
| `monto_usd` | `decimal(12,2)` | no | |
| `tasa_cambio` | `decimal(12,4)` | no | TRM aplicada, siempre editable a mano |
| `monto_cop` | `decimal(14,2)` | no | **Calculado y persistido** por `PagoObserver` |
| `origen_tasa` | `varchar(20)` | no | `OrigenTasaCambio`: `api` o `manual` |
| `referencia` | `varchar(255)` | sí | Número de transferencia |
| `notas` | `text` | sí | |

El **unique en `periodo_id`** es lo que hace real la regla "un periodo, un pago". Sin
él es solo una intención.

### `contadores_tarea`

Último número de tarea entregado para un proyecto.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `proyecto_id` | `bigint unsigned` | no | → `proyectos` · **cascade** · **UNIQUE** |
| `ultimo_numero` | `int unsigned` | no | Por defecto `0` |

Esta tabla no estaba en el diseño original: se añadió para resolver la condición de
carrera del código correlativo. Ver
[`modulos/tareas.md`](modulos/tareas.md#decisiones-de-diseño).

### `tareas`

Unidad de trabajo.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `proyecto_id` | `bigint unsigned` | no | → `proyectos` · **cascade** |
| `modulo_id` | `bigint unsigned` | sí | → `modulos` · **set null** |
| `periodo_id` | `bigint unsigned` | sí | → `periodos` · **set null**. Periodo actual |
| `periodo_origen_id` | `bigint unsigned` | sí | → `periodos` · **set null**. De dónde se arrastró |
| `tarea_origen_id` | `bigint unsigned` | sí | → `tareas` · **set null**. La entrega que originó esta corrección |
| `codigo` | `varchar(255)` | no | **unique**. `{PREFIJO}-{NNNN}` |
| `nombre` | `varchar(255)` | no | |
| `descripcion` | `text` | sí | |
| `estado` | `varchar(20)` | no | `EstadoTarea`, por defecto `pendiente` |
| `tipo` | `varchar(20)` | no | `TipoTarea`. **No cambia** |
| `prioridad` | `varchar(20)` | no | `PrioridadTarea` |
| `motivo_espera` | `text` | sí | Obligatorio al pasar a `en_espera` |
| `motivo_cancelacion` | `text` | sí | Obligatorio al pasar a `cancelada` |
| `fecha_inicio` | `date` | sí | |
| `fecha_fin` | `date` | sí | |
| `orden` | `int` | no | Por defecto `0`. Orden manual en tablero y Kanban |
| `creado_por` | `bigint unsigned` | no | → `users` · **restrict** |
| `deleted_at` | `timestamp` | sí | Borrado suave |

Índices: `(proyecto_id, estado)`, `(periodo_id, estado)`, `(proyecto_id, modulo_id)`,
`(tarea_origen_id)`.

**No hay ninguna columna de horas.** El sistema no las registra en ninguna parte.

**`motivo_espera` y `motivo_cancelacion` son campos distintos** y no se reutilizan
entre sí: son cosas diferentes y se consultan en momentos diferentes.

**`tarea_origen_id` es autorreferencial y `set null`.** Solo lo llevan las tareas de tipo
`correccion` y `soporte`, siempre apuntando a otra tarea del mismo proyecto, sin
apuntarse a sí mismas y sin cerrar cadenas circulares; las cuatro invariantes las
comprueba `TareaObserver` (D3.3). Es `set null` porque una corrección sigue siendo
trabajo hecho aunque desaparezca la entrega que la originó, y perderla falsearía el
reporte de su periodo.

**`fecha_inicio` no se sobrescribe y `fecha_fin` sí.** La primera marca cuándo se empezó
de verdad; la segunda, cuándo quedó lista, y se actualiza si una revisión fallida obliga a
retocar (D2.13, D2.14). Las rellena `CambiarEstadoTarea`, no un formulario.

### `notas_tarea`

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `tarea_id` | `bigint unsigned` | no | → `tareas` · **cascade** |
| `usuario_id` | `bigint unsigned` | no | → `users` · **restrict** |
| `contenido` | `text` | no | |
| `visibilidad` | `varchar(20)` | no | `Visibilidad`, por defecto `interna` |

### `historial_estados`

Un registro por cambio de estado. Lo escribe `TareaObserver`, nunca un controlador.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `tarea_id` | `bigint unsigned` | no | → `tareas` · **cascade** |
| `usuario_id` | `bigint unsigned` | sí | → `users` · **set null**. Nulo si el cambio no vino de una sesión |
| `estado_anterior` | `varchar(20)` | sí | |
| `estado_nuevo` | `varchar(20)` | no | |
| `comentario` | `text` | sí | El motivo exigido por el destino, si lo hubo |
| `created_at` | `timestamp` | sí | **No hay `updated_at`**: un registro histórico no se corrige |

### `reuniones`, `asistentes_reunion`, `reunion_tarea`

`reuniones` guarda el contenido enriquecido **dos veces**: `contenido_json` es el
documento TipTap editable y `contenido_html` la copia renderizada para mostrar y
buscar sin volver a interpretar el JSON.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `proyecto_id` | `bigint unsigned` | no | → `proyectos` · **cascade** |
| `fecha` | `datetime` | no | |
| `tipo` | `varchar(20)` | no | `TipoReunion`: reunión, llamada, correo, mensaje |
| `titulo` | `varchar(255)` | no | |
| `contenido_json` | `json` | no | Documento TipTap |
| `contenido_html` | `longtext` | no | Render |
| `acuerdos` | `text` | sí | |
| `visibilidad` | `varchar(20)` | no | Por defecto `interna` |
| `creado_por` | `bigint unsigned` | no | → `users` · **restrict** |

`asistentes_reunion` lleva `nombre` **obligatorio incluso cuando hay `usuario_id`**: la
gente del cliente no es usuaria del sistema, y el nombre debe sobrevivir aunque el
usuario se borre. `reunion_tarea` es un pivote simple con unique compuesto: vincula una
reunión con las tareas que nacieron de ella.

### `reportes`

Registro permanente de la emisión de un reporte.

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `proyecto_id` | `bigint unsigned` | no | → `proyectos` · **cascade** |
| `periodo_id` | `bigint unsigned` | no | → `periodos` · **cascade** |
| `tipo` | `varchar(20)` | no | `TipoReporte`: `completo` o `cliente` |
| `nombre_archivo` | `varchar(255)` | no | Con sufijo obligatorio `_completo` / `_cliente` |
| `filtros` | `json` | no | Con qué filtros se generó |
| `resumen_estados` | `json` | no | Conteo por estado |
| `snapshot` | `json` | no | **Las filas exactas exportadas** |
| `total_tareas` | `int` | no | |
| `hash` | `varchar(40)` | no | Huella del contenido exportado |
| `generado_por` | `bigint unsigned` | no | → `users` · **restrict** |
| `generado_en` | `datetime` | no | Sin `created_at` / `updated_at` |

**No hay columna de ruta de archivo, y no debe añadirse.** Los `.xlsx` no se persisten
en disco: se generan bajo demanda. El `snapshot` es lo único que permite reconstruir
meses después el archivo idéntico al que se envió; sin él, regenerar en noviembre el
reporte de julio daría un archivo distinto y la trazabilidad sería ficticia.

### `vistas_guardadas`

| Columna | Tipo | Nulo | Significado |
|---|---|---|---|
| `usuario_id` | `bigint unsigned` | no | → `users` · **cascade** |
| `proyecto_id` | `bigint unsigned` | sí | → `proyectos` · **cascade**. Nulo = vista global |
| `nombre` | `varchar(255)` | no | |
| `tipo` | `varchar(20)` | no | `tabla`, `kanban`, `lista`, `calendario` |
| `configuracion` | `json` | no | Filtros, agrupación y columnas |
| `es_predeterminada` | `tinyint(1)` | no | Por defecto `0` |
