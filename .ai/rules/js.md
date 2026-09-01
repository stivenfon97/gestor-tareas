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

## El estado de un listado va en la URL, no en memoria
Toda vista de listado refleja su estado —orden, página, filtros y columnas visibles— en la URL. Un listado cuyo estado solo vive en memoria no se puede compartir ni recuperar tras recargar: «las tareas en espera del proyecto X» deja de ser un enlace y pasa a ser una instrucción de siete pasos. Las vistas guardadas de la fase 08 son literalmente eso: una cadena de consulta con nombre.

Lo resuelve `composables/useEstadoDeTabla.ts`, que vive **fuera** de `components/TablaDatos/` porque el Kanban comparte filtros y orden con el listado aunque no comparta la forma.

El flujo es de un solo sentido: URL → servidor → props → lo que se pinta, y las acciones del composable vuelven a la URL. El composable **no guarda copia del estado**, lo lee de getters que apuntan a las props; con una copia local, una petición fallida o el botón «atrás» dejan las dos fuentes separadas.

La excepción es la densidad de fila (`useDensidad`), que va a `localStorage`: es una preferencia de quien mira, no parte de lo que se comparte. Mismo criterio para la pestaña activa de una página con secciones, que sí va en la URL (`?seccion=tareas`).

## `trans` se importa de `@/lib/i18n`, nunca del paquete
El paquete devuelve la clave misma cuando falta la traducción y eso se pinta como texto normal. El envoltorio de `@/lib/i18n` avisa por consola y la marca en pantalla como `⟦clave⟧` en desarrollo. Hay una regla de ESLint (`no-restricted-imports`) que impide el atajo.

Si añades una clave a un componente, añádela a `lang/es` y a `lang/en` en el mismo commit: `CoberturaDeClavesTest` recorre los componentes, extrae las claves y falla si falta alguna en cualquiera de los dos idiomas.

Una clave que viaja como dato (`{ title: 'nav.dashboard' }` en `AppSidebar`, que `NavMain` pasa por `$t`) también la caza el extractor, pero solo entre **comillas simples**: en una plantilla Vue el valor de un atributo va entre dobles y `:alt="auth.user.name"` tiene la misma forma que una clave.

## Un literal visible en la plantilla no es una clave sin resolver, y ninguna comprobación de humo lo ve
Hay dos maneras distintas de que un texto salga mal y **no se detectan igual**:

1. La clave no resuelve → se pinta su ruta (`nav.dashboard`). Lo cazan `CoberturaDeClavesTest`, el modo estricto de `@/lib/i18n` y `make humo`.
2. El texto nunca pasó por el sistema de traducción → un `Email address` escrito a mano se renderiza perfectamente, solo que en el idioma equivocado. **Ninguna de las tres anteriores lo ve**: `make humo` busca cadenas con forma de clave y esa no la tiene. Así sobrevivieron las pantallas de acceso en inglés hasta la fase 07c.

Del segundo se encarga `vue/no-bare-strings-in-template` en `npm run lint`. Cubre el texto de la plantilla y los atributos que se muestran (`title`, `alt`, `placeholder`, `aria-label`). **No cubre** los literales del `<script>` ni los que se pasan como prop: para esos, el valor es una **clave** y la resuelve con `$t` quien la pinta (`NavMain`, `Breadcrumbs`, `AuthSimpleLayout`, `settings/Layout`). En `defineOptions` no se puede llamar a `trans()`: se evalúa al importar el módulo, antes de que i18n exista.

Las excepciones se declaran en `eslint.config.js` con el motivo al lado, o con `<!-- eslint-disable-next-line vue/no-bare-strings-in-template (motivo) -->` en el archivo. Nunca desactivando la regla entera: una regla sin escape se apaga en cuanto estorba.

## Escala de acciones: cuatro escalones, y la destructiva nunca en la posición primaria
Primaria `default` (relleno esmeralda), una por pantalla como mucho: lo que se viene a hacer ahí. Secundaria `outline`: alternativas del mismo nivel y todo lo que cambia estado sin destruir. Terciaria `ghost`: navegación y deshacer de bajo riesgo. Destructiva `destructive`, **fuera de la posición primaria** —menú de acciones secundarias o pie de la pantalla de edición— y con confirmación proporcional al daño: lo que arrastra otras entidades detrás confirma escribiendo el nombre (`components/ConfirmarPorNombre.vue`), no con un «¿seguro?» que se contesta sin leer.

No uses `variant="secondary"` para acciones: es un relleno gris que no ocupa ningún escalón. Queda para `Badge`.

Una acción de estado tiene forma de control (`outline`, nunca `ghost` suelto a la derecha) y su rótulo dice qué va a pasar, no en qué estado está: «Desactivar» en un cliente activo, «Activar» en uno inactivo.

Detalle y razones en docs/05-identidad-visual.md §13.
