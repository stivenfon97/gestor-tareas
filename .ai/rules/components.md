---
paths:
  - 'resources/js/components/**'
---

# Components

## Prohibido cualquier color literal en componentes
Sin hex (`#10b981`), sin utilidades crudas de paleta (`bg-yellow-100`, `text-blue-600`, `border-red-500`). **Solo tokens semánticos** definidos en el tema.

Razón: un literal como `bg-yellow-100` no tiene par oscuro. En modo oscuro queda texto claro sobre fondo claro y el badge se vuelve ilegible. El token sí define ambos modos.

Cada componente debe verse correcto en modo claro **y** oscuro. Compruébalo antes de dar el trabajo por terminado.

Jerarquía visual: solo el **estado** lleva relleno de color; el **tipo** va como icono monocromo y la **prioridad** como barra vertical en el borde. El esmeralda de marca queda reservado a acciones primarias y nunca se usa como color de estado.

## Cabecera de página y margen de contenedor están resueltos en un sitio
El relleno del contenido vive **una sola vez**, en `AppSidebarLayout` (`px-bloque py-bloque md:px-seccion md:py-seccion`). No lo repitas en cada página: eso fue lo que dejó unas pantallas pegadas al canto del panel y otras no.

Ese contenedor lleva `min-w-0`. Sin él, un hijo de un flex no baja de su ancho de contenido y una tabla ancha empuja el documento entero en lugar de desplazarse dentro de su panel.

Toda pantalla usa `components/CabeceraDePagina.vue`: título (`<h1>`), descripción opcional, slot `acciones` a la derecha, slot `meta` para badges, y borde inferior. No compongas otra a mano.

**Sin mayúsculas forzadas en ningún sitio, incluidas las cabeceras `<th>`.** La fase 07 hizo una excepción con el `<th>` y la fase 07b la retiró viendo una captura: con acentos la caja alta grita a 11px, y además no se aplicaba a todas —el contenido de una columna ordenable es un `<button>`, que no hereda `text-transform`, así que media fila salía en caja mixta y media en alta.
