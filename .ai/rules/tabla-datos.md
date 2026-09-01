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

## Por debajo de `md` la tabla se apila como tarjetas, y las columnas las declara el listado
Mantener seis columnas en 360px con desplazamiento horizontal es una tabla que nadie desplaza. Por debajo de `(max-width: 767.98px)` cada fila es una tarjeta.

**El componente no adivina qué se ve.** Cada listado marca sus columnas: `principal` (arriba, sin etiqueta, se admite más de una) y `prioritaria` (par etiqueta/valor). El resto va tras «Ver más». Sin `principal` se usa la `fija`, que es la que lleva el enlace; por posición saldría «Tipo» en tareas.

Los 0.02px del corte no son un detalle: `md:` de Tailwind empieza en 768px, y con el corte en `768px` exacto hay un ancho en el que el cajón del menú ya está activo y las utilidades `md:` también.

La decisión va con `useMediaQuery`, no con `hidden md:table`: pintar las dos formas duplica cada fila en el DOM y el tabulador itinerante se mueve por filas que nadie ve. A cambio, al cruzar el corte hay que volver a medir los anclajes y a sacar del tabulador lo que hay dentro de las filas (`watch(esMovil)`).

Los filtros de columna viven bajo la cabecera, que en modo tarjeta no existe: los sirve `PanelDeFiltros`. Si lo quitas, desde el teléfono no se puede filtrar.
