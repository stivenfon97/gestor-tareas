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

## Las pestañas de un proyecto son rutas propias bajo un layout persistente
Cada sección de un proyecto (módulos, equipo, tareas/tablero/lista/agenda, periodos, pagos, bitácora, reportes) es su propia ruta y su propia página, envuelta por `layouts/proyecto/Layout.vue` (registrado por nombre de página en `PAGINAS_DE_PROYECTO` de `app.ts`). Nunca pestañas que solo muestran un contador y un botón «Ver» que saca del proyecto: así estaba antes y nadie volvió a llenarlas.
- El controlador de la sección añade `'cabeceraDeProyecto' => PresentarCabeceraDeProyecto::para($proyecto, $request->user())`. Sin esa prop el layout no pinta nada propio (el listado global de tareas reutiliza `tareas/Index`).
- Una pestaña se lista si la policy de **su ruta** lo permite (`pestanas.*` en el servicio): nunca se ofrece una que dé 403.
- Dentro de la sección el título va con `EncabezadoDeSeccion` (`<h2>`), no `CabeceraDePagina`: el `<h1>` es el del proyecto. Sin botón «Volver».
- Una sección nueva = ruta + página en `PAGINAS_DE_PROYECTO` + entrada en `pestanas` del servicio y del layout.
- Los enlaces antiguos `proyectos/{id}?seccion=x` redirigen a su ruta (`ProyectoController::RUTAS_DE_SECCIONES_ANTERIORES`).
