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

Los filtros de texto de columna cuelgan de la cabecera, que en modo tarjeta no existe: los sirve `PanelDeFiltros`. Si lo quitas, desde el teléfono no se puede filtrar por texto. Los de opciones van en la barra a todos los anchos.

## Los filtros no heredan el ancho de su columna y la tabla acaba en fila entera
**Ningún control de filtro hereda el ancho de su columna.** El `<select>` de «Tipo» salía como `Tc` porque la celda medía 40px. Ensanchar esa columna solo mueve el problema a la siguiente columna estrecha: el disparador es un icono de tamaño fijo en la cabecera (`FiltroDeCabecera.vue`) y el control vive en una capa flotante que se dimensiona por su contenido. `data-filtro-de-columna` y `data-filtro-cabecera` son lo que `make humo` abre y mide.

**La tabla acaba siempre en una fila entera.** `altoMaximo` es un límite en píxeles sobre un contenido medido en filas y caía en mitad de una. `TablaDatos` recorta ese límite al pie de la última fila que cabe, midiendo las filas del DOM: `alto de fila × n` falla en cuanto un nombre ocupa dos líneas. El scroll vertical no se puede sacar del panel, porque el `<thead>` pegajoso necesita un contenedor que ruede.

Dónde van la búsqueda, los filtros, el menú «Opciones» y la paginación lo fija la regla «Barra de herramientas arriba y pie de paginación siempre visible», más abajo.

## Barra de herramientas arriba y pie de paginación siempre visible
Sustituye a las decisiones anteriores de «paginación oculta con una página» y «controles solo en el pie»; las pidió retirar el dueño del producto al ver la tabla sin forma visible de filtrar ni paginar.
- Arriba, dentro del panel: `BuscadorDeTabla` (prop `busqueda`), un `FiltroFacetado` por cada filtro de opciones —aunque su columna esté oculta—, fichas de los filtros de texto activos y «Limpiar filtros»; a la derecha el slot `acciones` y `MenuCompacto`.
- Los filtros de opciones ya no cuelgan de la cabecera de la columna; en la cabecera solo quedan los de texto que no son la búsqueda.
- `PaginacionDeTabla` se pinta siempre: rango «Mostrando x–y de n», tamaño de página, página actual y primera/anterior/siguiente/última (deshabilitados con una sola página).
