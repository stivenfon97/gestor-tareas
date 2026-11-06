---
paths:
  - 'app/Actions/**'
---

# Actions

## Un update() de query builder no deja historial: regístralo a mano
El historial de cambios lo escribe un observer, y un `update()`/`insert()` de query builder no dispara eventos de Eloquent. Toda escritura masiva sobre un modelo auditado que cambie un dato con significado (no `orden`) llama a `RegistrarHistorial::actualizacionesMasivas()` dentro de la misma transacción, leyendo antes los valores previos. Precedentes: el traslado de `CerrarPeriodo` y el cambio de padre en `ReordenarModulos`. La membresía de proyecto funciona porque el pivote usa `->using(ProyectoUsuario::class)`; sin `using()`, attach/detach no dejarían rastro.
