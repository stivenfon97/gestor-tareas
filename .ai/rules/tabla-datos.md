---
paths:
  - 'resources/js/components/TablaDatos/**'
---

# Tabla Datos

## TablaDatos no conoce ningún dominio
Columnas, filtros y acciones se declaran **desde fuera**, en la página. Dentro de `TablaDatos/` no puede aparecer ni una referencia a tareas, clientes o proyectos: ni un `if (recurso === 'tareas')`, ni un texto de dominio, ni un valor por defecto pensado para un listado concreto. Los textos genéricos viven en `comun.tabla.*`; los del dominio los pasa la página por props (`vacio`, `etiqueta`, `etiqueta` de cada columna).

Tampoco trae tema propio: consume los tokens de `app.css`.

**Si un listado necesita sobrescribir sus estilos desde fuera —un `!important`, una clase inyectada, un `:deep()`— la API del componente está mal diseñada. Se arregla la API, no se parchea el listado.**

`ordenPor` de una columna tiene que coincidir con un `allowedSorts` del controlador y `filtro.clave` con un `allowedFilters`; si no coinciden, spatie/laravel-query-builder devuelve 400, que es preferible a ignorar el parámetro en silencio. Detalle en `docs/modulos/tabla-de-datos.md`.
