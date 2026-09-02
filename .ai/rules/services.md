---
paths:
  - 'app/Services/**'
  - app/Services/CambiarEstadoTarea.php
---

# Services

## El rol global `invitado` nunca ve contenido interno
`RolGlobal::Invitado` implica siempre `veLoInterno() === false`, sea cual sea su rol de proyecto. "Ser del cliente" es condición de la persona, no del proyecto: si dependiera solo del rol de proyecto, elegir mal en un desplegable sería una filtración.

Se aplica en **dos** sitios, y el segundo es el que se olvida:

1. `ResolverRolProyecto::veLoInterno()` exige que las dos capas digan que sí.
2. Los scopes de listado (`Reunion`, `NotaTarea`) usan `ResolverRolProyecto::proyectosSinAccesoInterno()`, **no** `proyectosDondeEsCliente()`. El segundo devuelve una lista vacía para un `invitado` sin rol de proyecto `cliente`, así que ningún listado se le recorta y lee los títulos internos aunque el detalle dé 403.

No te apoyes en que a `invitado` le falte un permiso (`pagos.ver`): una cerradura que funciona de rebote no es una cerradura.

## Ninguna vía de escritura cambia `estado` directamente sobre el modelo
Todo cambio de estado de una tarea pasa por `App\Services\CambiarEstadoTarea`. Un `$tarea->update(['estado' => …])` suelto en un controlador, comando o acción se salta las **fechas automáticas** (`fecha_inicio` / `fecha_fin`), que solo el servicio rellena.

Reparto, que no es duplicación sino profundidad:

- **El servicio** valida contra la matriz antes de tocar la base, rellena las fechas y devuelve el estado nuevo con sus transiciones disponibles.
- **`TareaObserver@updating`** revalida y exige el motivo: es la última red para lo que no pase por el servicio (seeder, comando, acción masiva).
- **`TareaObserver@updated`** escribe `historial_estados`. **Nunca el servicio**: si el historial dependiera de que alguien llame al servicio, algún día habría un camino que no lo llama y quedaría un hueco silencioso.

El estado tiene endpoint propio (`PUT tareas/{tarea}/estado`, `EstadoTareaController`) y **no viaja en el formulario general**: mezclarlo permitiría saltarse la matriz enviándolo entre los demás campos. Autoriza con `cambiarEstado`, no con `update`.

## Ninguna consulta a un servicio externo bloquea el registro de datos del usuario
`ConsultarTrm` es el patrón: tiempo de espera corto (3s), un solo intento (sin `retry()`), y cualquier fallo —caída, timeout, forma de respuesta inesperada— se traga devolviendo `null` en lugar de propagar una excepción. El formulario que consume el servicio (registro de pago) sigue funcionando con captura manual; `origen_tasa` queda como `manual`. Se cachea solo un valor resuelto (`Cache::forever` por fecha), nunca un fallo, para que una caída puntual no deje una clave "envenenada" sin reintentar. Cualquier integración externa nueva sigue este mismo contrato.
