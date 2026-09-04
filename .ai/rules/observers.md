---
paths:
  - 'app/Observers/**'
---

# Observers

## El historial de estados y el código correlativo se generan en observers
El historial de estados y el código correlativo de tarea se generan en observers, **nunca desde un controlador**. Escribirlos a mano en un controlador significa que algún día alguien no se acuerda —un comando de consola, una acción masiva, un seeder— y el dato se pierde en silencio.

Reparto dentro de `TareaObserver`:
- `creating`: precarga la prioridad según el tipo, exige el motivo del estado inicial y asigna el código.
- `updating`: valida la transición contra `EstadoTarea::puedeTransicionarA()`, exige el motivo del destino y limpia el del estado que se abandona. Va aquí porque tiene que poder abortar el guardado.
- `updated`: escribe la fila en `historial_estados`. Va aquí, y no en `updating`, porque el UPDATE puede fallar después (interbloqueo, restricción) y dejaría registrado un cambio que nunca ocurrió.

El código sale de `contadores_tarea` con `lockForUpdate()`, no de `max(codigo) + 1`: dos creaciones simultáneas leerían el mismo máximo, y un borrado forzado haría que un código se reutilizara. El `unique` de `codigo` es la última red, no la primera.

## PagoObserver recalcula monto_cop siempre, ignora lo que le pasen
`PagoObserver::saving()` reescribe `monto_cop` en cada guardado como `bcmul(monto_usd, tasa_cambio)` redondeado a dos decimales. Da igual qué valor traiga el modelo o la factory en ese campo: se pisa siempre. Para fijar un `monto_cop` concreto en un test o seeder, no lo pases directo — pasa `monto_usd` y `tasa_cambio` cuyo producto dé el resultado que quieres.
