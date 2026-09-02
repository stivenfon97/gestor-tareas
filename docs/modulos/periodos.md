# Módulo: Periodos

## Propósito

Un periodo es un bloque de trabajo facturable de un proyecto: una fecha de inicio, una
fecha de fin y un pago. Es la unidad con la que se cobra y con la que se rinde cuentas
al cliente. Informalmente equivale a "unas cien horas", pero **eso es contexto de
negocio, no un dato que se capture**: el sistema no registra horas.

## Modelo de datos

Tablas: **`periodos`** y, a través de ellas, **`tareas`** (`periodo_id` y
`periodo_origen_id`) y **`reportes`**. Diccionario completo en
[`../02-modelo-de-datos.md`](../02-modelo-de-datos.md).

- **`fecha_fin` es nullable.** Vale como fecha *prevista* mientras el periodo sigue
  `abierto` —editable desde el detalle, avisa de vencimiento— y el cierre siempre la
  sustituye por la fecha real, sin excepción.
- **Unique en `(proyecto_id, consecutivo)`.** El consecutivo es por proyecto.
- **`proyecto_si_abierto`** (columna generada `VIRTUAL`, fase 09) vale `proyecto_id`
  cuando `estado = 'abierto'` y `NULL` en cualquier otro caso, con un índice único
  encima: es lo que hace imposible un segundo periodo abierto del mismo proyecto a
  nivel de base de datos.
- **`estado`** es `EstadoPeriodo`: `abierto` o `cerrado`.
- Borrar un periodo **no borra sus tareas**: quedan con `periodo_id` nulo (`set null`).
  No hay ruta de borrado: `PeriodoPolicy::delete` siempre niega.

Relaciones del modelo `Periodo`: `proyecto`, `pago` (**`hasOne`**, no `hasMany`),
`tareas`, `tareasArrastradas`, `reportes`. Métodos: `estaVencido()`,
`duracionEnDias()`. Scopes: `abiertos()`, `cerrados()`.

## Reglas de negocio

1. **Los periodos son por proyecto, no por cliente** (D4.1).
2. **Un periodo tiene exactamente un pago** (D4.2), garantizado por el `unique` en
   `pagos.periodo_id`.
3. **El cierre es manual** (D4.3). El sistema nunca cierra un periodo solo: no hay job
   programado ni observer que lo haga al llegar la fecha de fin.
4. **Sobrepasar la fecha de fin no es un error** (D4.4). Es un estado válido y
   frecuente: la interfaz lo señala, la validación no lo rechaza.
5. **Una tarea no terminada puede arrastrarse al siguiente periodo** conservando
   `periodo_origen_id` (D4.7).
6. **No se registran horas** por periodo ni por tarea (D1.5).
7. **Un solo periodo abierto por proyecto** (D4.8), garantizado por la base de datos.
8. **Un periodo cerrado no admite tareas nuevas ni traslados hacia él** (D4.9).
9. **Trasladar una tarea no es un cambio de estado** (D4.10): conserva el suyo, no pasa
   por `CambiarEstadoTarea`, no escribe en `historial_estados`, y no toca `orden`.
10. **El cierre es una operación en lote, en una sola transacción** (D4.11).

## Flujo principal

El cierre, de principio a fin: [`../diagramas/flujo-cierre-periodo.md`](../diagramas/flujo-cierre-periodo.md).

```mermaid
flowchart LR
    abierto[["Periodo abierto"]] -->|CierrePeriodoController@create| pantalla["Pantalla de cierre"]
    pantalla -->|CierrePeriodoController@store| accion["CerrarPeriodo (transacción)"]
    accion --> cerrado["Periodo cerrado, fecha_fin = hoy"]
    accion --> trasladadas["Tareas seleccionadas → periodo siguiente"]
    accion --> nuevo["Periodo siguiente, abierto"]
    accion -.opcional.-> pago["Pago registrado"]
```

## Rutas y controladores

| Método | URI | Controlador | Permiso requerido |
|---|---|---|---|
| GET | `proyectos/{p}/periodos` | `PeriodoController@index` | `viewAny` |
| POST | `proyectos/{p}/periodos` | `PeriodoController@store` (vía `AbrirPeriodo`) | `create` |
| GET | `periodos/{periodo}` | `PeriodoController@show` | `view` |
| PUT | `periodos/{periodo}` | `PeriodoController@update` | `update` |
| GET | `periodos/{periodo}/cierre` | `CierrePeriodoController@create` | `cerrar` |
| POST | `periodos/{periodo}/cierre` | `CierrePeriodoController@store` (vía `CerrarPeriodo`) | `cerrar` |

## Permisos

| Rol | Puede |
|---|---|
| Administrador | Todo, vía `Gate::before`. |
| Líder | Abrir, editar y cerrar periodos de sus proyectos (`RolProyecto::gestionaElProyecto()` + `Permiso::PeriodosCerrar` para cerrar). |
| Colaborador | Ver periodos y sus tareas. No abre, no edita, no cierra. |
| Observador | Ver periodos. |
| Cliente | Ver periodos —son el marco de su reporte— y su desglose de tareas. No ve el pago que cuelga de ellos: eso lo niega `PagoPolicy`. |

## Decisiones de diseño

### `fecha_fin` nullable, con doble uso mientras el periodo sigue abierto

**Alternativa descartada en la fase 03: `fecha_fin` obligatoria desde el inicio**, con el
cierre limitándose a cambiar el estado. Se descartó porque forzar el dato desde el
principio invita a tratarlo como un compromiso, y confundir "cuándo se espera terminar"
con "cuándo se terminó" es exactamente el error que D4.4 existe para evitar.

La fase 09 la deja **nullable y editable mientras el periodo sigue `abierto`**: es una
fecha prevista, orientativa, que alimenta el aviso de vencimiento
(`Periodo::estaVencido()`) y que el cierre **siempre sustituye** por la fecha real
(`now()`), la haya puesto alguien o no. La columna nunca deja de significar "cuándo
terminó de verdad" una vez el periodo está `cerrado`, que es la garantía que importa.

### Un solo periodo abierto: columna generada `VIRTUAL`, no `STORED`

Mismo mecanismo que la vista predeterminada de la fase 08 —columna generada que vale
`proyecto_id` en `abierto` y `NULL` en cualquier otro caso, con un índice único
encima—, pero con una vuelta que no tuvo la anterior: la expresión lee `proyecto_id`,
que **tiene clave foránea** (`periodos_proyecto_id_foreign`). En MySQL 8.4, una columna
generada `STORED` que depende de una columna con FK revienta el `ALTER TABLE` con
`Cannot add foreign key constraint` (error 1215) aunque la sentencia no toque ninguna
FK —verificado a mano, persiste incluso con `FOREIGN_KEY_CHECKS=0`—: es una limitación
del algoritmo que usa InnoDB para reconstruir la tabla, no una comprobación de
integridad. `VIRTUAL` no reconstruye filas y no tropieza con eso; MySQL indexa columnas
virtuales sin problema desde 5.7, y SQLite las soporta igual que las `STORED`.

### El cierre es una pantalla propia, no una acción del tablero

**Alternativa descartada: un botón "Cerrar" en el tablero o en el detalle del
periodo**, que dispare la operación de golpe. El cierre mueve varias tareas a la vez,
abre un periodo nuevo y opcionalmente registra un pago: es una operación en lote con
una decisión por tarea (¿se traslada o se queda?) detrás. Meterla en un botón sin
pantalla propia deja al líder sin ver qué se va a mover antes de confirmarlo, que es
justo lo que un cierre —irreversible una vez hecho— no puede permitirse.

### Trasladar no reutiliza `CambiarEstadoTarea`

La tentación es real: el servicio ya sabe mover tareas y escribir su rastro. Pero
trasladar **no cambia el estado**, y forzarlo por el servicio de transiciones exigiría
inventar una transición "hacia el mismo estado" que ensuciaría `historial_estados` con
algo que nunca ocurrió: un cambio de estado que no fue tal. `CerrarPeriodo` actualiza
`periodo_id` y `periodo_origen_id` con un `update()` de query builder —sin disparar el
observer de `Tarea`— precisamente para que quede imposible confundir las dos cosas.

### `orden` sigue siendo del proyecto; el traslado no lo toca

La fase 08 dejó la pregunta abierta: al trasladar tareas entre periodos, ¿`orden` pasa
a ser relativo al periodo, o sigue siendo del proyecto?

Se decidió que **sigue siendo del proyecto**, sin cambios. La razón es que `orden` ya
no tiene un significado propio fuera de la consulta que lo usa: cada vista que lo lee
—columna del tablero por estado, módulo o prioridad; grupo de la lista por módulo,
periodo o estado— ordena con su propio `WHERE`, así que dos tareas de periodos
distintos nunca compiten por la misma posición. Renumerar al trasladar obligaría a
decidir dónde entra cada tarea arrastrada dentro del periodo nuevo —¿al principio? ¿al
final?— y esa pregunta no tiene una respuesta mejor que dejar que la reordenación
manual de la fase 08 la resuelva si hace falta.

## Puntos de extensión

- **Editar la fecha de inicio de un periodo ya con tareas.** Hoy se permite sin más
  comprobación mientras esté abierto; si algún día importa que las tareas no queden
  con una fecha "antes de que existiera" el periodo, la validación va aquí.
