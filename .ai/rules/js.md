---
paths:
  - 'resources/js/**'
---

# Js

## Ningún texto visible se escribe directo en el componente
Todo texto que el usuario ve pasa por el helper de traducción (`laravel-vue-i18n`). Nunca literales en la plantilla:

```vue
<!-- mal -->
<button>Cerrar periodo</button>
<!-- bien -->
<button>{{ $t('periodos.cerrar') }}</button>
```

Aplica también a `placeholder`, `title`, `aria-label`, mensajes de validación y textos de estado vacío. Español es el idioma base; inglés está preparado.

## Árboles recursivos: clave por id y un nivel completo por petición
En un componente recursivo (hoy `components/modulos/ModuloNodo.vue`) la clave de renderizado es **el id, nunca el índice**. Con el índice, mover un nodo hace que Vue reutilice el DOM del hermano que ocupaba esa posición: la rama colapsada aparece colapsada en otro nodo y el campo en edición salta al equivocado. Se lee como "el arrastre corrompe el árbol" cuando los datos están bien.

Por lo mismo, el nodo recibe sus hijos por `defineModel` en lugar de escribir dentro de la prop.

La reordenación persiste **el nivel completo en una sola petición** (`PUT proyectos/{p}/modulos/orden`, payload `niveles: [{parent_id, modulos: [ids]}]`), nunca una petición por nodo movido: un arrastre cambia varios hermanos y diez peticiones seguidas dejan el árbol a medias si una falla. Mantén una copia del último árbol confirmado por el servidor y revierte en `onError`.

## La interfaz nunca deduce qué transiciones de estado son válidas
Las transiciones disponibles llegan del servidor con cada tarea (`transiciones`, de `CambiarEstadoTarea::disponiblesPara()`). **No replique la matriz en Vue**: sería la cuarta fuente de verdad —enum, skill, diagrama y cliente— y la única que `MatrizDeTransicionesTest` no comprueba.

Cada transición trae `requiere_motivo` con **el nombre del campo** (`motivo_espera` o `motivo_cancelacion`), no un booleano: son campos distintos y el formulario debe enviar el correcto.

Para los tres ejes visuales, las clases de color van en un **mapa estático con las clases escritas enteras** (`BadgeEstado.vue`, `BarraPrioridad.vue`). Nunca `bg-estado-${estado}-fondo`: el escáner de Tailwind lee el código como texto, la utilidad no se genera y el badge sale **sin color solo en el build de producción**. Da siempre una reserva neutra por si el servidor envía un valor inesperado.
