---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Controladores delgados y listados vía query-builder
Los controladores **no contienen lógica de negocio**: delegan en servicios o acciones. Un controlador válido resuelve autorización, valida la request, llama a una acción y devuelve la respuesta.

Toda consulta a listados pasa por `spatie/laravel-query-builder`, nunca por filtros armados a mano desde la request:

```php
// mal
if ($request->has('estado')) { $q->where('estado', $request->estado); }
// bien
QueryBuilder::for(Tarea::class)->allowedFilters(['estado', 'tipo', 'prioridad'])
```

Armar filtros a mano deja huecos de autorización y expone columnas que no se pensaban filtrar.

## El estado de una tarea no se toca desde un controlador
Ningún controlador escribe `estado` sobre una `Tarea`. El único que lo cambia es `EstadoTareaController`, y delega en `App\Services\CambiarEstadoTarea`.

`TareaRequest` no acepta `estado` ni `tipo`: el primero tiene endpoint propio y el segundo se fija al crear y no cambia (D1.3). Si añades un endpoint que mueva tareas —el Kanban de la fase 08, una acción masiva—, llama al servicio.

El controlador tampoco recalcula qué transiciones son válidas: las pide con `CambiarEstadoTarea::disponiblesPara()` y las envía a la interfaz.
