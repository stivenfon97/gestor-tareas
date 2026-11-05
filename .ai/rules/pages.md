---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Toda pantalla sigue la skill interfaz-estandar
Antes de crear o tocar una página, lee `.ai/skills/interfaz-estandar/SKILL.md`. Es la norma del dueño del producto para todas las pantallas:
- `CabeceraDePagina` con `icono` obligatorio, el mismo que la sección lleva en `AppSidebar.vue`. `Heading` solo para subtítulos internos.
- Un listado de registros es siempre `TablaDatos`, con `busqueda`, filtros de opciones visibles y paginación del servidor (`EstadoDeTabla::porPagina`); nunca un `<table>` a mano ni `->get()`.
- Las acciones de fila son `BotonDeAccion` (icono + `tono` + `etiqueta` para el tooltip), nunca botones con texto. Solo la acción primaria de la cabecera lleva texto.
- Los valores de un conjunto cerrado (estado, rol…) son `Badge` con la variante de `resources/js/lib/tonos.ts`; `secondary`/`outline` no sirven para categorías.
