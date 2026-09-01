---
paths:
  - 'resources/js/layouts/**'
---

# Layouts

## Una sola frontera por pantalla, y la altura la manda el contenido
El área de contenido **no pinta un panel**. El menú lateral es `variant="sidebar"` (columna con borde derecho, sin margen ni redondeo) y el contenido se apoya sobre el fondo de la página. Con la variante `inset` había dos cajas concéntricas sin jerarquía y la tabla se leía como un objeto flotando dentro de otro.

El contenedor de contenido en `AppSidebarLayout` **no lleva `flex-1`**: estirarlo hasta el pie de la ventana era lo que dejaba seiscientos píxeles de panel vacío bajo un listado de tres filas. La altura la manda el contenido; debajo se ve el fondo.

Lleva `mx-auto max-w-contenido` (`--container-contenido: 90rem`). No muerde por debajo de 1440px de ventana y sí en 1600 y 1920. Dentro de ese tope, lo que ocupa todo el ancho depende de para qué sirve: la tabla sí (alinear columnas es su trabajo), un resumen de tres datos no.

La miga de pan solo aparece con más de un nivel: con uno repetía el `<h1>` de justo debajo.

Razones en docs/05-identidad-visual.md §12.
