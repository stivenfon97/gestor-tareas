# Módulo: Tabla de datos

> Construido en la **fase 07**. Es el componente que sirve todos los listados del
> proyecto y el que van a reutilizar las fases siguientes.

## Propósito

`TablaDatos` es una tabla genérica sobre [TanStack Table v8](https://tanstack.com/table)
que resuelve, de una vez y para todos los listados, lo que cada pantalla estaba
resolviendo por su cuenta: ordenar, filtrar, paginar, elegir columnas y recorrerse con
el teclado.

**No conoce ningún dominio.** Dentro de `resources/js/components/TablaDatos/` no hay una
sola referencia a tareas, clientes o proyectos: columnas, filtros y acciones se declaran
desde la página. Esa es la restricción que lo hace reutilizable y la que hay que
defender cuando aparezca la tentación de meterle un caso particular.

## Piezas

| Archivo | Qué es |
|---|---|
| `TablaDatos/TablaDatos.vue` | El componente |
| `TablaDatos/tipos.ts` | La API pública: `ColumnaDeTabla`, `FiltroDeColumna`, `AccionEnLote`… |
| `TablaDatos/CabeceraDeColumna.vue` | Etiqueta, control de orden y prioridad de orden |
| `TablaDatos/FiltroDeColumna.vue` | El control: filtro de texto o de opciones |
| `TablaDatos/FiltroDeCabecera.vue` | Disparador y capa flotante del filtro, en la cabecera |
| `TablaDatos/LeyendaDeColumna.vue` | Clave de lectura de una columna, en la cabecera |
| `TablaDatos/MenuCompacto.vue` | Los cuatro controles agrupados, en el pie |
| `TablaDatos/TarjetaDeFila.vue` | Una fila apilada como tarjeta (por debajo de `md`) |
| `TablaDatos/PanelDeFiltros.vue` | Los filtros de columna, apilados (por debajo de `md`) |
| `TablaDatos/EstadoVacio.vue` | Los dos vacíos |
| `TablaDatos/EsqueletoDeTabla.vue` | Carga sin saltos de diseño |
| `composables/useEstadoDeTabla.ts` | Sincronización del estado con la URL |
| `composables/useDensidad.ts` | Densidad de fila, por navegador |
| `app/Services/EstadoDeTabla.php` | El lado servidor: traduce los parámetros a props |

Los dos composables viven **fuera** de la carpeta del componente a propósito: el Kanban
de la fase 08 comparte filtros y orden con el listado aunque no comparta la forma.

## Cómo se declara un listado

```vue
<script setup lang="ts">
import { TablaDatos, type ColumnaDeTabla, type OrdenDeColumna } from '@/components/TablaDatos';

const props = defineProps<{
    tareas: RespuestaPaginada<FilaTarea>;
    filtros: Record<string, string>;
    orden: OrdenDeColumna[];
    columnasVisibles: string[] | null;
}>();

const columnas = computed((): ColumnaDeTabla<FilaTarea>[] => [
    {
        id: 'codigo',                   // viaja en el parámetro `columnas` de la URL
        etiqueta: trans('tareas.codigo'),
        valor: (tarea) => tarea.codigo, // valor por defecto de la celda
        ordenPor: 'codigo',             // campo de `allowedSorts` del controlador
        anclada: true,                  // se queda pegada al hacer scroll horizontal
        fija: true,                     // no se puede ocultar
        mono: true,
        anchoMinimo: '10rem',
    },
    {
        id: 'estado',
        etiqueta: trans('comun.estado'),
        valor: (tarea) => tarea.estado,
        ordenPor: 'estado',
        filtro: {
            tipo: 'opciones',
            clave: 'estado',            // campo de `allowedFilters` del controlador
            opciones: [/* … */],
        },
    },
]);
</script>

<template>
    <TablaDatos
        :url="index(proyecto.id).url"
        :filas="props.tareas.data"
        :columnas="columnas"
        :clave="(tarea) => tarea.id"
        :orden="props.orden"
        :filtros="props.filtros"
        :columnas-visibles="props.columnasVisibles"
        :paginacion="props.tareas"
        :etiqueta="$t('tareas.titulo')"
        :vacio="{ titulo: …, cuerpo: … }"
        @abrir="abrir"
    >
        <template #celda-estado="{ fila }">
            <BadgeEstado :estado="fila.estado" tamano="pequeno" />
        </template>
    </TablaDatos>
</template>
```

### Props

| Prop | Qué es |
|---|---|
| `url` | Ruta del listado. La tabla navega a ella con los parámetros nuevos |
| `filas`, `columnas`, `clave` | Los datos y su declaración |
| `orden`, `filtros`, `columnasVisibles`, `paginacion` | El estado **tal y como lo devolvió el servidor** |
| `etiqueta` | Rótulo accesible; va al `<caption>` |
| `vacio` | Texto del vacío «todavía no hay datos». El otro vacío es genérico |
| `seleccionable`, `accionesEnLote` | Selección de filas y qué se puede hacer con ella |
| `cargando`, `error` | Esqueleto y aviso de error |
| `altoMaximo` | Longitud CSS. Va a `style`, nunca a una clase |

### Slots

- **`celda-{id}`** — contenido de una celda. Recibe `{ fila, valor }`.
- **`leyenda-{id}`** — clave de lectura de esa columna. Si el slot existe, la cabecera
  pinta un botón de información que la abre. Es lo que explica una columna de símbolos:
  el componente no sabe qué significa un icono, la página sí.
- **`acciones`** — controles propios de la página, en el pie junto a «Opciones».
- **`vacio-acciones`** — la salida del vacío inicial (normalmente «crear»).

### Eventos

- **`abrir(fila)`** — Intro sobre la fila enfocada, o doble clic. Solo en la tabla: en
  modo tarjeta se abre por el enlace de la celda, que ya está ahí.
- **`enLote(accion, claves)`** — una acción en lote sobre las filas seleccionadas.

### Correspondencia con el servidor

`ordenPor` tiene que coincidir con un `allowedSorts` del controlador y `filtro.clave` con
un `allowedFilters`. Si no coinciden, spatie/laravel-query-builder responde **400**, que
es preferible a ignorar el parámetro en silencio y que la tabla mienta sobre su estado.

## Por debajo de `md`: tarjetas, no tabla

Añadido en la fase 07b. El corte es `(max-width: 767.98px)`, el mismo que usa el menú
lateral. Mantener seis columnas en 360px con desplazamiento horizontal produce una tabla
que nadie desplaza: se ve la primera columna y media segunda, y el resto no existe.

Cada fila pasa a ser una tarjeta. **Qué se ve lo declara el listado**, con dos marcas
nuevas en `ColumnaDeTabla`:

| Marca | Dónde sale |
|---|---|
| `principal` | Arriba de la tarjeta, **sin etiqueta**, una por línea. Se admite más de una |
| `prioritaria` | Par etiqueta/valor, visible sin desplegar |
| ninguna | Detrás de «Ver más» (`<details>`) |

```ts
{
    id: 'codigo',
    etiqueta: trans('tareas.codigo'),
    valor: (tarea) => tarea.codigo,
    fija: true,
    principal: true,   // identifica la fila
},
{
    id: 'nombre',
    etiqueta: trans('comun.nombre'),
    valor: (tarea) => tarea.nombre,
    principal: true,   // es lo que se lee
},
{
    id: 'estado',
    etiqueta: trans('comun.estado'),
    valor: (tarea) => tarea.estado,
    prioritaria: true, // acompaña, con su etiqueta
},
```

**El componente no lo adivina.** Sin `principal` declarada usa la columna `fija`, que es
la que lleva el enlace; adivinar por posición daría «Tipo» en el listado de tareas.

Los slots `celda-{id}` siguen sirviendo igual: la tarjeta cambia la forma, no quién sabe
pintar cada valor.

Lo demás que cambia por debajo de `md`:

- Los filtros de columna se apilan en un `<details>` encima del panel. En la tabla
  cuelgan de la cabecera de cada columna, y ahí no hay cabecera; sin esto no se podría
  filtrar desde el teléfono.
- El pie deja el total y «anterior / siguiente». El selector de tamaño de página y el
  «página X de Y» se ocultan. El menú «Opciones» sigue ahí: es el mismo a todos los
  anchos.
- `altoMaximo` **no se aplica**: un contenedor con su propio scroll dentro de una página
  que también rueda es un pulgar peleándose con dos ejes.

La decisión se toma con `useMediaQuery`, no con `hidden md:table`: pintar las dos formas
duplicaría cada fila en el DOM y el tabulador itinerante se movería por filas que nadie
ve. A cambio, un `watch` sobre la consulta vuelve a medir los anclajes y a sacar del
tabulador lo que hay dentro de las filas cuando se cruza el corte.

## Qué **no** debe hacerse

> **No se sobrescriben sus estilos desde fuera.** Si un listado necesita un `!important`,
> una clase inyectada o un `:deep()` para que la tabla se vea como quiere, el problema es
> la API del componente, no el listado: se arregla la API.

Tampoco:

- Meterle conocimiento de dominio. Ni un `if (recurso === 'tareas')`.
- Darle colores. Consume los tokens de `app.css` y no trae tema propio.
- Guardar el estado solo en memoria. Ver la sección siguiente.
- Construir clases de Tailwind por interpolación. Los mapas son objetos literales con las
  clases escritas enteras, y se comprueban en el CSS compilado.

## El estado vive en la URL

Orden, página, filtros y columnas visibles van en la barra de direcciones:

```
/proyectos/3/tareas?filter[estado]=en_espera&sort=-prioridad,codigo&columnas=codigo,nombre,estado&page=2
```

Un listado cuyo estado solo vive en memoria no se puede compartir ni recuperar tras
recargar: «las tareas en espera del proyecto X» deja de ser un enlace y pasa a ser una
instrucción de siete pasos.

El flujo es de un solo sentido:

```
URL ──► servidor ──► props ──► lo que se pinta
 ▲                                  │
 └──────── useEstadoDeTabla ◄───────┘
```

`useEstadoDeTabla` **no guarda copia del estado**: lo lee de getters que apuntan a las
props. Así no hay dos fuentes de verdad que puedan separarse cuando una petición falla o
el usuario usa el botón «atrás».

**La densidad es la excepción** y va a `localStorage`. Es una preferencia de quien mira,
no parte de lo que se comparte: enviarle a alguien un enlace y forzarle además el tamaño
de fila sería decidir por él con qué comodidad lee.

## Orden multicolumna

Un clic en la cabecera cicla ascendente → descendente → sin orden, y deja ese campo como
único orden. **Mayús+clic** añade el campo al orden existente o cicla dentro de él.

El número junto a la flecha cuando hay más de un campo no es decoración: sin él,
«ordenado por prioridad y por código» y «ordenado por código y por prioridad» se ven
exactamente igual y dan resultados distintos.

En el servidor viaja como `sort=-prioridad,codigo` y llega a la interfaz **ya
descompuesto** en lista ordenada (`EstadoDeTabla::orden()`), porque esa posición es
justamente la información que la cabecera necesita para dibujarse.

## Teclado

Se usa muchas horas al día, así que la tabla se recorre entera sin ratón.

| Tecla | Qué hace |
|---|---|
| `Tab` | Cabeceras, con sus leyendas y filtros → primera fila → pie, con «Opciones» |
| `↑` `↓` | Fila anterior / siguiente |
| `Inicio` `Fin` | Primera / última fila de la página |
| `Intro` | Abre la fila enfocada |
| `Espacio` | Marca y desmarca la fila (si el listado es seleccionable) |

**Tabulador itinerante**: solo la fila enfocada es alcanzable con `Tab`, y lo interactivo
que hay dentro de las filas se saca del orden de tabulación. Sin eso, con dos enlaces por
fila y treinta filas, llegar al pie costaría sesenta pulsaciones y las flechas serían un
adorno. Los enlaces siguen en el árbol de accesibilidad: se quitan del recorrido de `Tab`,
no de los lectores de pantalla.

El foco es **siempre visible** (`:focus-visible` con contorno de marca en `app.css`).
Nunca `outline: none` sin sustituto.

## Estructura semántica

`<table>` de verdad, con `<caption>` accesible, `<th scope="col">` y `aria-sort` en las
columnas ordenadas. Un `<div>` con `role="grid"` habría dado el mismo aspecto y peor
comportamiento: la navegación por tabla de los lectores de pantalla, el `Ctrl+F` del
navegador y la copia al portapapeles funcionan con una tabla y no con divs.

## Los dos estados vacíos

| Situación | Salida |
|---|---|
| No hay datos todavía | Botón primario: crear |
| Los filtros no devuelven nada | Botón secundario: limpiar filtros |

Cuál de los dos es **no se adivina**: el componente lo sabe porque el servidor le dice si
hay filtros activos. Confundirlos es el fallo habitual —al que filtró mal se le ofrece
«crea la primera tarea» cuando tiene doscientas, y se queda pensando que perdió datos—.

El estado de error muestra **el mensaje real del servidor**. Un «algo salió mal» le quita
al único lector de esta aplicación —una persona técnica— lo único con lo que podría
arreglarlo.

## Decisiones de diseño

### Los desplazamientos de las columnas ancladas se miden, no se declaran

Una columna anclada necesita saber a qué distancia del borde izquierdo se pega. La
alternativa obvia —que cada columna declare su ancho exacto— rompe en cuanto el ancho
declarado y el real difieren en un píxel: queda un hueco o un solape **justo en la
costura**, que es el sitio donde más se nota porque es donde el contenido pasa por
debajo. Se lee del DOM con `offsetLeft` y se vuelve a medir con un `ResizeObserver`.

### La paginación es `paginate()`, no `simplePaginate()`

Se mantiene la decisión de la fase 06: se quiere «página 3 de 12», y el `COUNT(*)` no se
nota con estos volúmenes. Un «cargar más» no dice cuánto queda, que es justo lo que se
pregunta al revisar un periodo.

### La selección se limpia al cambiar de página

Una página nueva es un conjunto de filas nuevo. Conservar la selección llevaría a aplicar
una acción en lote a filas que ya no se ven, que es la clase de sorpresa que hace que la
gente deje de usar las acciones en lote.

### El tamaño de página está acotado

`EstadoDeTabla::porPagina()` solo admite 15, 30, 50 y 100. Sin tope, un `por_pagina=100000`
en la barra de direcciones descarga el proyecto entero de una vez.

### `manualSorting`, `manualFiltering` y `manualPagination`

TanStack aquí es el **modelo de columnas y de selección**, no un motor de datos en el
cliente. Ordenar y filtrar en el navegador solo ordenaría y filtraría la página que ya se
ve, que es peor que no hacerlo porque parece que funciona.

## Puntos de extensión

- **Fase 08 (Kanban y vistas guardadas).** El Kanban no reutiliza `TablaDatos` —no
  comparten forma— pero sí `useEstadoDeTabla`: filtros y orden son los mismos y ya están
  en la URL. Las vistas guardadas son, literalmente, una cadena de consulta con nombre.
- **Fases 09, 10 y 11.** Periodos, pagos, reuniones y reportes sustituyen sus
  `recursos/Listado.vue` provisionales por este componente.
- **Acciones en lote reales.** Hoy la única es copiar códigos, que no necesita servidor.
  Mover tareas de periodo en bloque es trabajo de la fase 09 y ya tiene sitio donde
  engancharse.

### Los filtros no viven en la maquetación de la tabla

El `<select>` de «Tipo» se renderizaba como `Tc`: la columna lleva un icono, mide cuarenta
píxeles y el control medía lo que la celda.

Ensanchar esa columna solo movía el problema a la siguiente columna estrecha. **Ningún
control de filtro hereda el ancho de su columna**: en la cabecera hay un disparador de
tamaño fijo y el control vive en una capa flotante, que se dimensiona por su contenido.
Con eso desapareció además la segunda fila del `<thead>`.

Los controles llevan `data-filtro-de-columna` y los disparadores `data-filtro-cabecera`:
`make humo` los abre uno a uno y falla si alguno mide menos de 96px. Los abre de verdad
—una capa cerrada no existe en el DOM visible— y falla también si no llegó a medir
ninguno, que es la forma silenciosa de dar verde en vacío.

### La tabla acaba en una fila entera

`altoMaximo` es un límite en píxeles sobre un contenido medido en filas, así que caía casi
siempre en mitad de una y la última fila visible salía partida. El componente recorta ese
límite hasta el pie de la última fila que cabe entera.

Se miden las filas del DOM, no `alto de fila × n`: una fila con el nombre en dos líneas
mide más que sus vecinas. Cuando el contenido cabe entero no se recorta nada, y el panel
queda ajustado a su contenido.

El desplazamiento vertical no se puede sacar del panel: un `<thead>` pegajoso necesita un
contenedor que ruede, y ese contenedor tiene que rodar también en horizontal por las
columnas ancladas.

### Con una sola página no hay controles de paginación

«Por página · Página 1 de 1 · ‹ ›» con `last_page` a uno son cuatro controles que no
llevan a ninguna parte: dos nacen deshabilitados y el tercero informa de que no hay nada
más. Queda el total de resultados, que sí dice algo.
