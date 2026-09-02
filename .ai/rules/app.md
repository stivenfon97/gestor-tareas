---
paths:
  - 'app/**'
---

# App

## Estado, tipo y prioridad son tres ejes independientes
Una tarea se clasifica en **tres columnas independientes que no se mezclan**:

- **Estado** — dónde está en el ciclo: `pendiente`, `en_proceso`, `en_espera`, `terminada`, `entregada`, `cancelada`.
- **Tipo** — naturaleza del trabajo: `desarrollo`, `correccion`, `soporte`, `mejora`, `investigacion`. No cambia.
- **Prioridad** — `baja`, `media`, `alta`, `critica`. Cambia con el tiempo.

**"Corrección" y "Soporte" son TIPOS, no estados.** Recorren el mismo ciclo de vida que cualquier otra tarea: una corrección puede estar `pendiente`, `en_proceso` o `entregada`. Convertirlos en estados es el error más frecuente en este dominio y rompe la matriz de transiciones.

Regla de conveniencia: al crear una tarea de tipo `correccion` o `soporte`, la prioridad se **precarga** en `alta`. Sigue siendo editable — es un valor por defecto, no una restricción.

## No se registran horas en ninguna parte del sistema
El sistema **no registra horas**. Ni por tarea, ni por periodo, ni en ningún otro sitio. No hay `horas_estimadas`, `horas_trabajadas`, cronómetros ni timesheets.

Un periodo es informalmente "unas 100 horas", pero eso es contexto de negocio, no un dato que se capture. Los periodos se delimitan por fechas y se cierran a mano.

Si un requisito parece necesitar horas, la respuesta es que no las necesita: revísalo antes de añadir la columna.

## Un periodo tiene un pago, se cierra a mano y puede sobrepasar su fecha de fin
- **Los periodos son por proyecto**, no por cliente.
- **Un periodo tiene exactamente un pago.** Relación uno a uno, no uno a muchos.
- **El cierre es manual.** El sistema **nunca** cierra un periodo solo: no hay job programado ni observer que lo haga al llegar la fecha de fin.
- **Sobrepasar la fecha de fin no es un error.** Un periodo abierto más allá de su fecha de fin es un estado válido y frecuente. La interfaz lo señala visualmente; la validación no lo rechaza.
- **La TRM se consulta a la fecha del pago**, no a la fecha de hoy, y el valor queda siempre editable a mano.

El código de tarea es correlativo **por proyecto y continuo de por vida** (`MAQ-0001`, `MAQ-0002`…). No se reinicia por periodo.

## Trasladar una tarea entre periodos no es un cambio de estado
Al cerrar un periodo, las tareas que se trasladan al siguiente conservan su `estado`: el traslado actualiza `periodo_id` y `periodo_origen_id` con un `update()` de query builder (`CerrarPeriodo`), nunca con `$tarea->save()` en un bucle ni pasando por `CambiarEstadoTarea`. Reutilizar el servicio de transiciones para esto ensuciaría `historial_estados` con una transición que nunca ocurrió: el estado no cambia, solo el periodo. `orden` tampoco se toca al trasladar: sigue siendo del proyecto, no del periodo (D4.10 en docs/03-dominio.md).
