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

## Lo que un rol no puede ver tampoco se serializa
Ocultar un dato en la interfaz no lo oculta: el JSON de Inertia viaja completo y se lee abriendo las herramientas del navegador (así se destapó el bug de PeriodoController en la fase 09, que mandaba el pago entero aunque `verPagos` fuera falso).

Cuando un dato no debe verse para cierto rol, la clave **falta del todo** en el array que se pasa a `Inertia::render()` — nunca viaja vacía ni con un flag `null` que la esconda solo en la interfaz. Patrón: computa la condición antes, y añade la clave condicionalmente con `+ ($condicion ? ['clave' => ...] : [])`. Ejemplos ya aplicados: `respaldos` en `ProyectoController::show` (solo si `ResolverRolProyecto::gestiona()`), `adjuntos` en `TareaController::show` (solo si `veLoInterno()`, porque los adjuntos de tarea no tienen marca de visibilidad propia y se tratan siempre como internos).

Toda prueba de visibilidad comprueba la respuesta (`$respuesta->viewData('page')['props']`), no la pantalla.

## Inertia::defer() necesita un grupo propio por cada bloque independiente
Sin segundo argumento, todo `Inertia::defer(fn () => …)` de una misma página cae en el grupo `'default'` y el cliente los pide todos en **una sola** petición: un bloque lento retrasa a los demás. Para que cada bloque cargue en paralelo, dale un nombre de grupo propio y distinto: `Inertia::defer(fn () => …, 'nombre_del_bloque')`. Precedente: `DashboardController` (fase 12), cuatro bloques con cuatro grupos.
