# Identidad visual

El plan de diseño del sistema, la autocrítica que se le hizo antes de construirlo y el
inventario completo de tokens que salió de ahí.

Para las reglas de color de los tres ejes de la tarea —qué estado lleva qué familia, qué
contraste exige— manda `.ai/skills/colores-y-tema/SKILL.md`. Este documento no las
repite: las da por buenas y define todo lo demás.

---

## 1. El encargo

Una herramienta de trabajo diario para un desarrollador freelance que lleva varios
proyectos a la vez. Se usa muchas horas seguidas, casi siempre para **leer listas densas
de tareas** y para **capturar información rápido**. El público es una persona técnica.

De ahí sale el criterio que decide todas las dudas de este documento:

> El trabajo de la interfaz es hacer legible mucha información a la vez y no estorbar.
> Cuando una decisión de diseño y la densidad de lectura se pelean, gana la densidad.

Eso descarta de entrada el lenguaje de las aplicaciones de marketing: nada de héroes,
ilustraciones decorativas, tarjetas con sombra idénticas para todo, ni degradados de
adorno. La referencia mental es un **instrumento de trabajo** —un cliente de correo, un
depurador, una hoja de cálculo— no una página de producto.

### Restricciones fijadas de antemano

| Restricción | Valor |
|---|---|
| Color de marca | **Esmeralda**, reservado a acciones primarias |
| Base neutra | **Stone** |
| Tipografía de interfaz | **Plus Jakarta Sans** |
| Tipografía de datos | **JetBrains Mono** |
| Modos | Claro y oscuro, **ambos de primera clase** |
| Tokens de estado | Los doce ya existen y su contraste está verificado. **No se tocan** |

---

## 2. Escala tipográfica

Seis tamaños. Ni uno suelto elegido sobre la marcha: si un texto no encaja en la escala,
el problema es el texto.

| Token | Utilidad | Tamaño / interlínea | Tracking | Dónde |
|---|---|---|---|---|
| `--text-micro` | `text-micro` | 11px / 16px | +0.04em | Cabeceras de tabla, contadores, metadatos |
| `--text-fino` | `text-fino` | 12px / 16px | +0.01em | Texto secundario de fila, ayudas de formulario |
| `--text-cuerpo` | `text-cuerpo` | 13px / 20px | 0 | **Cuerpo de tabla, formularios, casi todo** |
| `--text-medio` | `text-medio` | 15px / 22px | −0.005em | Títulos de sección, nombres de entidad en detalle |
| `--text-alto` | `text-alto` | 18px / 26px | −0.01em | Título de página |
| `--text-cima` | `text-cima` | 24px / 32px | −0.015em | Solo pantallas de una sola cosa: acceso, error, vacío inicial |

**Los nombres no chocan con la escala numérica de Tailwind a propósito.** Redefinir
`--text-sm` habría movido de golpe las cuarenta pantallas que aún no se han migrado,
incluidas las de fases futuras. Con nombres propios conviven las dos escalas: lo migrado
usa `text-cuerpo`, lo pendiente sigue en `text-sm` (14px) hasta que le toque, y la
diferencia de un píxel entre ambas no es un defecto visible.

**El cuerpo es 13px, no 14 ni 16.** Es la decisión tipográfica con más consecuencias del
documento. Un listado de tareas a 16px cabe a razón de trece filas por pantalla; a 13px
caben veinte. Para un lector técnico que mira la misma tabla ocho horas al día, la
diferencia entre ver el periodo entero y tener que hacer scroll es más importante que la
comodidad marginal de dos píxeles.

### Pesos

Tres, y ninguno más:

- **400** — todo el cuerpo.
- **500** — el dato que identifica la fila (nombre de la tarea, del cliente), cabeceras
  de tabla, botones.
- **600** — títulos de sección y de página.

**No se usa 700 en ningún sitio de la interfaz.** Plus Jakarta Sans en negrita es muy
oscura; con negritas repartidas por la pantalla, la jerarquía se aplana y todo grita
igual. Donde se necesita más énfasis que 600, se sube de tamaño, no de peso.

### Tracking

Plus Jakarta Sans se abre a tamaños pequeños y se afloja a tamaños grandes. La escala lo
compensa: tracking positivo por debajo de 13px, cero en el cuerpo, negativo por encima.
Es la razón por la que cada fila de la tabla de arriba lleva su propio valor en vez de
heredar `normal`.

### La monoespaciada

**JetBrains Mono va a 12px cuando el cuerpo va a 13px.** Su altura de x es mayor que la
de Plus Jakarta Sans: al mismo tamaño nominal se ve más grande, y un código de tarea en
mono al lado de su nombre acabaría gritando más que el nombre, que es lo que de verdad
importa en la fila.

Se usa **solo en cuatro sitios**, y esto es una lista cerrada:

1. Códigos de tarea (`MAQ-0042`).
2. Fechas.
3. Montos y tasas de cambio.
4. Prefijos de proyecto.

Lo que tienen en común: son valores que se **comparan verticalmente** entre filas, y la
anchura fija hace el trabajo. Un contador, un número de periodo o un nombre no se
comparan carácter a carácter y en mono solo parecerían un identificador de máquina.

---

## 3. Escala de espaciado y densidad

Unidad base 4px. Nueve pasos, con nombre semántico donde el número no dice nada:

| Token | Utilidad | Valor | Uso |
|---|---|---|---|
| `--spacing-minimo` | `gap-minimo` | 2px | Separación entre un icono y su texto |
| `--spacing-fino` | `p-fino` | 4px | Interior de un badge |
| `--spacing-ajustado` | `py-ajustado` | 6px | Vertical de una celda compacta |
| `--spacing-corto` | `py-corto` | 8px | Vertical de una celda cómoda, controles hermanos |
| `--spacing-celda` | `px-celda` | 12px | **Horizontal de celda**, interior de un control |
| `--spacing-bloque` | `gap-bloque` | 16px | Separación entre bloques de un panel |
| `--spacing-seccion` | `gap-seccion` | 24px | Separación entre secciones |
| `--spacing-pagina` | `p-pagina` | 32px | Márgenes de página |
| `--spacing-vacio` | `py-vacio` | 48px | Aire alrededor de un estado vacío |

La unidad base sigue siendo la de Tailwind (4px). Lo que aporta el proyecto es el
**conjunto cerrado de nueve pasos con nombre**: `px-celda` dice por qué vale 12px y `px-3`
no dice nada. Un décimo paso solo se añade con una razón escrita aquí.

### Densidad de la tabla

Dos modos, conmutables desde la propia tabla y persistidos por navegador:

| Modo | Alto de fila | Padding vertical | Filas visibles a 900px de alto |
|---|---|---|---|
| **Cómoda** (por defecto) | 36px (`--spacing-fila-comoda`) | 8px | ~20 |
| **Compacta** | 28px (`--spacing-fila-compacta`) | 6px | ~26 |

El alto y el padding son **un solo token cada modo** y no dos clases sueltas: si se
mueven por separado, la cabecera fija deja de cuadrar con el cuerpo al hacer scroll.

El padding horizontal **no cambia con la densidad**: 12px siempre, 16px en la primera y
la última columna para que el contenido no toque el borde del panel. Estrechar también
en horizontal al compactar hace que las columnas se toquen y obliga a añadir líneas
verticales para separarlas, que es justo lo que la siguiente sección descarta.

La densidad por defecto es **cómoda**. La compacta es una herramienta para barrer un
periodo entero de un vistazo, no el estado de reposo de una aplicación que se mira ocho
horas.

---

## 4. Jerarquía de superficies

En modo oscuro las sombras no funcionan: sobre un fondo casi negro, una sombra gris no
se ve. Lo que sustituye a la elevación es **el valor de la superficie**, y la regla es
simétrica: en claro, cuanto más adelante, **más claro**; en oscuro, cuanto más adelante,
**más claro también**. La distancia entre niveles es de unos 3 puntos de luminosidad,
suficiente para leerse como un escalón sin dibujar cajas.

| Token | Qué es | Claro | Oscuro |
|---|---|---|---|
| `--color-fondo` | Fondo de página | stone 50 | stone casi negro |
| `--color-panel` | Contenedor de la tabla, de un formulario | blanco | stone 10% |
| `--color-panel-cabecera` | Cabecera fija de tabla, barra de herramientas | stone 50 | stone 13% |
| `--color-panel-flotante` | Menú, diálogo, tooltip | blanco | stone 16% |
| `--color-fila-hover` | Fila bajo el cursor o con foco | stone 100 | stone 14% |
| `--color-fila-activa` | Fila seleccionada | stone 150 | stone 18% |

Dos notas que no se deducen de la tabla:

- **La cabecera de tabla es más oscura que el panel en modo claro y más clara en
  oscuro.** No es incoherencia: la cabecera es cromo, no contenido, y en ambos modos se
  aparta del papel donde están los datos.
- **`--color-panel-flotante` es el único nivel que lleva sombra**, y solo en claro. En
  oscuro lleva un borde de `--color-borde-fuerte` en su lugar. Es la única concesión a
  las sombras en todo el sistema.

---

## 5. Bordes y divisores

En una tabla densa, los divisores hacen más por la legibilidad que cualquier otra cosa.
Cuatro decisiones:

**Solo divisores horizontales. Ninguna línea vertical.** El ojo ya alinea las columnas
por el espacio en blanco; añadir verticales convierte la tabla en una cuadrícula de
celdas y multiplica el ruido por el número de columnas. (La cuadrícula completa sí es
obligatoria en los Excel de la fase 11 — ahí el destinatario es un cliente que imprime,
no un operador que barre.)

**Sin bandas alternas (zebra).** El badge de estado ya lleva relleno de color. Un fondo
alterno mete un segundo ritmo de fondo que compite con él y hace que el estado se lea
peor, que es exactamente lo contrario de lo que se buscaba.

**Dos pesos de borde, no uno:**

| Token | Uso |
|---|---|
| `--color-borde` | Divisores entre filas, contorno de controles, separadores de menú |
| `--color-borde-fuerte` | Borde exterior del panel, **costura de la columna anclada**, borde de flotantes en oscuro |

La costura de la columna anclada es el único sitio donde aparece una línea vertical, y
lleva el borde fuerte a propósito: ahí el contenido pasa **por debajo** al hacer scroll
horizontal, y tiene que leerse como un canto, no como una pared de celda.

**Los divisores son de bajo contraste.** Aproximadamente 1.4:1 contra el panel. Un
divisor que se ve claramente es un divisor que compite con los datos; su trabajo es
guiar el ojo por la fila, no dibujarse a sí mismo.

### Radios

| Token | Valor | Uso |
|---|---|---|
| `--radius-sm` | 4px | Campos, badges, botones |
| `--radius-md` | 6px | Paneles, menús, diálogos |

No hay radio grande. Un radio de 12px o más es lenguaje de tarjeta de producto y aquí no
hay tarjetas de producto.

---

## 6. Estados vacíos y de error

Un listado vacío es una invitación a actuar, no un hueco. Y **no hay un solo vacío, hay
tres**, con salidas distintas:

| Situación | Título | Cuerpo | Salida |
|---|---|---|---|
| **No hay datos todavía** | «Todavía no hay tareas» | «Las tareas que crees en este proyecto aparecerán aquí.» | Botón primario: crear |
| **Los filtros no devuelven nada** | «Ningún resultado con estos filtros» | «Hay tareas en el proyecto, pero ninguna cumple lo que has pedido.» | Botón secundario: limpiar filtros |
| **Error al cargar** | «No se pudo cargar el listado» | El mensaje real del servidor, sin adornar | Botón: reintentar |

Confundir los dos primeros es el fallo habitual: al usuario que filtró mal se le ofrece
«crear la primera tarea» cuando tiene doscientas, y se queda pensando que perdió datos.
El componente los distingue porque el servidor le dice si hay filtros activos, no porque
adivine.

El estado de error **muestra el mensaje del servidor tal cual**. Un «algo salió mal» le
quita al único lector de esta aplicación —una persona técnica— la información con la que
podría arreglarlo.

### Estado de carga

Esqueleto con el **mismo número de filas y las mismas anchuras de columna** que la tabla
que va a aparecer. No un spinner centrado: un spinner colapsa la altura del contenedor y
la página da un salto cuando llegan los datos, justo cuando el usuario ya movió el
cursor a donde creía que iba a estar la primera fila.

---

## 7. Autocrítica

Lo que sigue es la revisión del plan anterior contra la pregunta «¿esto es lo que
produciría para cualquier otra aplicación?». Siete cosas no sobrevivieron.

### 7.1 — Todo troceado en tarjetas idénticas

**Lo que había.** El resumen del proyecto (`proyectos/Show.vue`) son tres bloques
`rounded-lg border p-4` idénticos: tareas por estado, periodo abierto y última reunión.
Mi primer plan mantenía ese patrón y lo extendía al resto de secciones.

**El defecto.** Tres cajas iguales dicen «aquí hay tres objetos del mismo tipo». Pero no
lo son: son **tres hechos sobre un mismo proyecto**. El borde no aporta información, solo
divide en tres lo que se lee mejor de corrido, y añade 2px de línea y 32px de padding a
cambio de nada.

**Qué se hizo.** El resumen deja de ser tres tarjetas y pasa a ser **una franja de datos
separados por divisores verticales**, sin borde exterior ni padding de caja. El borde
queda reservado a lo que de verdad es un contenedor con contenido desplazable dentro: el
panel de la tabla.

### 7.2 — Etiquetas en mayúsculas espaciadas encima de cada bloque

**Lo que había.** `text-micro uppercase tracking-wide` como estilo de etiqueta de
sección, encima de cada bloque.

**El defecto.** Es el tic de diseño de panel de administración más reconocible que
existe, y a 11px las mayúsculas se leen apreciablemente peor que la caja mixta.

**Qué se hizo.** Se prohíbe en toda la aplicación. Las etiquetas de sección van en
`--text-fino` con peso 500 y caja mixta.

> **Revisado en la fase 07b — la excepción del `<th>` queda retirada.**
>
> La fase 07 hizo una excepción con las cabeceras `<th>`, razonada sobre la marcha: la
> cabecera tiene que distinguirse del cuerpo a 11px, subirle el peso la hace competir con
> el nombre de la tarea y ponerle relleno de color choca con la regla de que solo el
> estado lleva relleno.
>
> El argumento no era malo. Lo que pasa es que **se tomó sin ver el resultado**: la
> aplicación mostraba entonces las claves de traducción en crudo, y `TAREAS.MODULO` no se
> lee ni mejor ni peor en mayúsculas. Con texto real en español la captura decide sola, y
> decide en contra:
>
> - Con acentos, la caja alta grita. `MÓDULO` y `PROYECTO` a 11px con `0.04em` de
>   tracking pesan visualmente más que el dato de la fila que anuncian, que es
>   exactamente lo que la fase 07 quería evitar subiéndole el peso.
> - Y sobre todo: **no se estaba aplicando a todas las cabeceras.** El `uppercase` vive en
>   el `<th>`, pero el contenido de una columna ordenable es un `<button>`, y el agente de
>   usuario no le hereda `text-transform`. El resultado eran seis cabeceras en dos cajas
>   distintas —`Código`, `Nombre` y `Estado` en caja mixta; `TIPO`, `PROYECTO` y `MÓDULO`
>   en alta— en la misma fila. Nadie lo vio en dos fases porque con las claves en crudo
>   parecía ruido más del mismo tipo.
>
> Las cabeceras van en caja mixta, con `--text-micro` y peso 500. Se distinguen del cuerpo
> por tamaño y por color (`--texto-suave` sobre `--panel-cabecera`), que ya bastaba.

### 7.3 — Monoespaciada para cualquier dato pequeño

**Lo que había.** «Datos en mono» como regla general.

**El defecto.** Barre hacia dentro contadores, números de periodo y conteos de miembros,
que en mono parecen identificadores de máquina y no cifras que se leen.

**Qué se hizo.** La lista cerrada de cuatro usos de la sección 2, con el criterio
explícito: mono solo donde el valor **se compara verticalmente entre filas**.

### 7.4 — Flechas «→» pegadas a enlaces y botones

**Lo que había.** «Ver módulos →» como patrón de enlace de sección.

**Qué se hizo.** Fuera, sin sustituto. La flecha no añade nada que el subrayado al pasar
el cursor no diga ya, y en una pantalla con veinte enlaces mete veinte glifos de ruido.

### 7.5 — Transiciones de hover en todo y entradas con desvanecido

**Lo que había.** `transition-colors` en filas, celdas, enlaces y botones; y una entrada
con desvanecido para cada sección.

**El defecto.** El fondo de fila es distinto: el cursor **barre** la tabla, cruzando diez
filas en medio segundo. Con 150ms de transición cada una arrastra su resaltado y lo que
se ve es una estela, que se lee como lentitud de la aplicación.

**Qué se hizo.** El resaltado de fila es **instantáneo, sin transición**. Se conservan
transiciones solo donde el cambio responde a un clic deliberado y ocurre una vez: apertura
de menús, desplegado de secciones, cambio de tema. Las entradas con desvanecido de sección
se eliminan por completo.

### 7.6 — Ocho tamaños de letra

**Lo que había.** Una escala de ocho pasos.

**El defecto.** Dos de ellos existían porque la escala «tenía que» ser completa, no
porque supiera dónde iban. Un tamaño sin uso asignado acaba usándose para arreglar un
caso concreto, y ahí muere la escala.

**Qué se hizo.** Seis, cada uno con su uso escrito en la tabla de la sección 2.

### 7.7 — Densidad compacta por defecto

**Lo que había.** Compacta por defecto: más información por pantalla, que es el criterio
declarado del encargo.

**El defecto.** El criterio no es «máxima información por pantalla», es «legible muchas
horas». A 28px de fila y 13px de letra, ocho horas seguidas cansan. La compacta es una
herramienta puntual —barrer un periodo entero— no una postura de reposo.

**Qué se hizo.** Por defecto **cómoda**; la compacta a un clic y recordada por navegador.

### Lo que sobrevivió sin cambios, y por qué

El cuerpo a 13px, los divisores solo horizontales y la jerarquía de superficies por
valor en vez de por sombra. Los tres salen directamente del encargo —listas densas, uso
prolongado, modo oscuro de primera clase— y no son lo que produciría por defecto: por
defecto habría escrito 14px, cuadrícula completa y `shadow-sm`.

---

## 8. Inventario de tokens

Todos viven en `resources/css/app.css`: los valores en `:root` y `.dark`, y el registro
en `@theme inline` que es lo que hace que Tailwind genere las utilidades.

### Marca y neutros

| Token | Utilidad que genera |
|---|---|
| `--color-marca`, `--color-marca-fuerte`, `--color-marca-contraste`, `--color-marca-suave` | `bg-marca`, `text-marca`, `border-marca`… |
| `--color-fondo`, `--color-panel`, `--color-panel-cabecera`, `--color-panel-flotante` | `bg-fondo`, `bg-panel`… |
| `--color-fila-hover`, `--color-fila-activa` | `bg-fila-hover`, `bg-fila-activa` |
| `--color-borde`, `--color-borde-fuerte` | `border-borde`, `border-borde-fuerte` |
| `--color-texto`, `--color-texto-suave`, `--color-texto-tenue` | `text-texto`, `text-texto-suave`… |

### Estados y prioridad

Los dieciocho de estado (seis estados × `-fondo`, `-texto`, `-borde`) y los cuatro de
prioridad ya existían desde la fase 02 y **no se han tocado**. Su tabla de pares
claro/oscuro y sus razones de contraste están en la skill `colores-y-tema`.

### Tipografía y espaciado

`--text-micro` … `--text-cima` y `--spacing-minimo` … `--spacing-vacio`, según las tablas
de las secciones 2 y 3, más `--spacing-fila-comoda` y `--spacing-fila-compacta` para la
densidad. Todos se registran en `@theme` y generan utilidades propias (`text-cuerpo`,
`px-celda`, `h-fila-comoda`).

### La trampa que hay que recordar

**El escáner de Tailwind 4 lee el código como texto estático.** Una clase construida en
tiempo de ejecución (`` `bg-${estado}-fondo` ``) no existe para él, la utilidad no se
genera, y el badge sale **sin color solo en el build de producción** —mucho después de
que alguien lo diera por terminado—.

Los mapas de clases son objetos literales con las clases escritas enteras
(`BadgeEstado.vue`, `BarraPrioridad.vue`), y se comprueba en el CSS compilado, no en
desarrollo:

```bash
docker compose exec node npm run build
grep -o 'bg-estado-[a-z-]*-fondo' public/build/assets/*.css | sort -u
```

---

## 9. Modo claro y oscuro

El modo oscuro va **por clase `.dark` en `<html>`, no por `prefers-color-scheme`**, y el
conmutador tiene tres posiciones: claro, oscuro y sistema. La preferencia se guarda en
`localStorage` y en una cookie, y se aplica antes del primer pintado.

Las tres posiciones son necesarias: con solo dos, elegir «claro» a mediodía deja la
aplicación en claro por la noche aunque todo el sistema haya cambiado. «Sistema» es una
elección, no la ausencia de elección, y por eso ocupa su propio botón.


---

## 10. Comportamiento responsive

Añadido en la fase 07b. El corte es **`md` (768px)**, y es el mismo en los tres sitios:
`SidebarProvider` usa `(max-width: 767.98px)` y `TablaDatos` la misma consulta. Los 0.02px
no son un detalle: con el corte en `768px` exacto hay un ancho en el que el cajón ya está
activo y las utilidades `md:` de Tailwind también, y la barra desaparece sin sustituto.

### 10.1 — Menú lateral

Por encima de `md` es una columna fija que se puede colapsar a iconos. Por debajo es un
**cajón** (`Sheet`) que se abre desde el botón de la cabecera y se cierra al tocar fuera.
Esto ya venía del starter kit; lo que la fase 07b arregló fue el corte y los textos del
lector de pantalla, que estaban escritos en inglés dentro del componente.

### 10.2 — Tabla

Por debajo de `md` **la tabla deja de ser una tabla**: cada fila pasa a ser una tarjeta
apilada. Mantener seis columnas en 360px con desplazamiento horizontal produce una tabla
que nadie desplaza —se ve la primera columna y media segunda, y el resto no existe.

Qué se ve en la tarjeta **lo declara cada listado**, no lo adivina el componente:

| Marca en la columna | Dónde sale en la tarjeta |
|---|---|
| `principal` | Arriba, sin etiqueta, una por línea. Se admite más de una. |
| `prioritaria` | Par etiqueta/valor, visible sin desplegar. |
| ninguna | Detrás de «Ver más», en un `<details>`. |

Sin `principal` declarada se usa la columna `fija`, que es la que lleva el enlace.
Adivinar por posición daría «Tipo» en el listado de tareas.

En tareas, `codigo` y `nombre` son las dos `principal`: el código identifica y el nombre
es lo que se lee. Ponerle «Nombre:» delante al nombre gasta ancho y no informa de nada.

La decisión entre tabla y tarjetas se toma con `useMediaQuery`, **no** con
`hidden md:table`: pintar las dos formas duplicaría cada fila en el DOM y el tabulador
itinerante acabaría moviéndose por filas que nadie ve. A cambio, al cruzar el corte hay
que volver a medir los anclajes de columna y a sacar del tabulador lo que hay dentro de
las filas, y de eso se encarga un `watch` sobre la consulta.

### 10.3 — Filtros

En la tabla los filtros viven en una fila bajo la cabecera. Con tarjetas no hay cabecera,
así que aparecen en un `<details>` **encima** del panel, plegado por defecto y **abierto si
ya hay algún filtro puesto**: al llegar por un enlace filtrado, lo primero que hay que
poder ver es por qué faltan filas.

Sin esto no se podría filtrar desde el teléfono, que es justo donde más falta hace.

### 10.4 — Barra de herramientas

Columnas, densidad, «limpiar filtros» y «limpiar orden» son cuatro controles que en 360px
no caben en una fila: o desbordan o se parten en tres. Por debajo de `md` se agrupan en un
único menú «Opciones», con las columnas en un submenú.

### 10.5 — Pie de la tabla

En móvil quedan el total y «anterior / siguiente». El selector de tamaño de página y el
«página X de Y» se ocultan: con el pulgar se pasa página, no se elige un tamaño.

El pie va **dentro** del borde del panel, junto a la cabecera y el cuerpo, para que se lea
como parte del mismo objeto y no como un texto suelto debajo.

Tampoco hay alto máximo en móvil: un contenedor con su propio scroll dentro de una página
que también rueda es un pulgar peleándose con dos ejes, y corta la última tarjeta por la
mitad.

---

## 11. Margen de contenedor y cabecera de página

Los dos defectos que hacían que la aplicación se leyera como un esqueleto sin terminar.

### 11.1 — Margen de contenedor

El contenido no toca el canto del panel. El relleno vive **una sola vez**, en
`AppSidebarLayout`, y no en cada página:

```
px-bloque py-bloque  →  16px por debajo de md
md:px-seccion md:py-seccion  →  24px a partir de md
```

Ese contenedor lleva `min-w-0`. Es lo que permite que una tabla ancha se desplace dentro
de su panel en lugar de empujar el documento entero: sin él, un hijo de un flex no baja de
su ancho de contenido y el documento desborda a lo ancho.

### 11.2 — Cabecera de página

Un solo patrón, en `components/CabeceraDePagina.vue`, para las cinco pantallas:

```
Título                                        [acciones]
Descripción opcional
[meta: badges, rol, prefijo]
──────────────────────────────────────────────────────── (borde inferior)
```

- El título es `--text-alto` con peso 600. Es un `<h1>`: hay uno por pantalla.
- Las acciones van a la derecha y **por debajo de `sm` bajan a su propia fila**, en lugar
  de estrujar el título.
- El slot `meta` es para lo que acompaña al título sin ser una acción. Va bajo la
  descripción y por encima de la línea.
- El borde inferior es lo que dice dónde acaba la cabecera y empieza el contenido. Antes
  el título y el botón flotaban sin nada que los separara de la tabla.

### 11.3 — Marcadores honestos

El tablero mostraba tres recuadros con rayas diagonales que parecían un error de carga: no
decían que faltara nada, simulaban contenido. Hasta la fase 12 cada bloque dice **qué va a
haber ahí**, en qué fase llega, y ofrece el camino que hoy sí existe.

**Nunca datos de mentira.** Una cifra inventada se olvida, nadie la distingue de un dato
real y acaba enseñándose a un cliente. Es la misma regla que ya seguía
`SeccionPendiente.vue` en el concentrador de proyecto.

---

## 12. Composición

Añadido en la fase 07d. La revisión con el texto ya traducido dio un veredicto que no
era sobre el diseño —tipografía, colores y densidad de fila funcionaban— sino sobre
**cómo estaba compuesto**: sobraba vacío, había marcos anidados sin jerarquía entre ellos
y tres cosas que parecían errores de renderizado.

### 12.1 — Una sola frontera por pantalla

Había dos cajas concéntricas: `SidebarInset` en variante `inset` pintaba un panel blanco
redondeado con sombra sobre toda el área de contenido, y dentro la tabla pintaba el suyo
con borde. Nada distinguía a una de otra, así que la tabla se leía como un objeto
flotando dentro de otro objeto.

Se retira la de fuera. El menú lateral pasa a `variant="sidebar"` —columna con borde
derecho, sin margen ni redondeo— y el contenido se apoya directamente sobre el fondo de
la página. **La única frontera de una pantalla es la que envuelve su contenido**, no la
que envuelve la página.

Consecuencia para los recuadros que quedaban dentro:

| Bloque | Qué queda |
|---|---|
| Listados | El panel de la tabla. Es la frontera. |
| Resumen de proyecto | Ninguna. Ya eran tres columnas con divisores, no tres tarjetas (§7.1); retirada la capa de fuera no hace falta añadir nada. |
| Tablero | Los tres bloques punteados se quedan: el borde discontinuo es lo que los marca como marcadores de posición, no como contenido (§11.3). |

### 12.2 — La altura la manda el contenido

En clientes había tres filas y después seiscientos píxeles de panel blanco. El panel no
sobraba de espacio: **se estiraba**. El contenedor de contenido llevaba `flex-1` y crecía
hasta el pie de la ventana hubiera tres filas o trescientas.

Se le quita el `flex-1`. Un listado de tres filas se ve corto, que es la verdad, y por
debajo se ve el fondo de la página.

Las otras dos direcciones se descartaron:

- **Rellenar el hueco con información útil** —últimos periodos, tareas abiertas por
  proyecto— es inventar un tablero dentro de un listado, y adelanta trabajo de las fases
  09 y 12. Un hueco es una respuesta honesta; un bloque puesto para taparlo, no.
- **Estirar el contenido** hasta ocupar la altura habría dado filas de sesenta píxeles en
  un listado de tres y de veinte en uno de treinta: la misma pantalla con dos densidades
  distintas según cuántos datos haya.

### 12.3 — El ancho también está acotado

`--container-contenido: 90rem` (1440px), aplicado en `AppSidebarLayout`. No muerde por
debajo de 1440px de ventana —ahí el ancho disponible ya es menor— y sí en 1600 y 1920,
donde una tabla de cinco columnas repartía mil seiscientos píxeles entre celdas medio
vacías.

Dentro de ese tope, **qué ocupa todo el ancho depende de para qué sirve**:

- La tabla, sí. Alinear columnas entre filas es su trabajo, y una tabla estrecha con
  desplazamiento horizontal es peor que una ancha.
- El resumen de proyecto, no (`max-w-4xl`). Tres hechos sobre el mismo proyecto se leen
  como un grupo; repartidos entre 1140px quedan uno en cada esquina de la pantalla.

### 12.4 — La tabla acaba en una fila entera

El desplazamiento vertical vive **dentro** del panel, y eso no es opcional: un `<thead>`
pegajoso necesita un contenedor que ruede, y ese contenedor tiene que rodar también en
horizontal por las columnas ancladas.

El defecto no era el `overflow`: era que el alto máximo (`70vh`) es un límite en píxeles
aplicado a un contenido medido en filas, y caía casi siempre en mitad de una. `TablaDatos`
recorta ahora ese límite hasta el pie de la última fila que cabe entera.

Se miden las filas de verdad, no `alto de fila × n`: una fila con el nombre en dos líneas
mide más que sus vecinas. Cuando el contenido cabe entero no se recorta nada, que es lo
que deja el panel ajustado a su contenido en los listados cortos.

### 12.5 — Los filtros cuelgan de la cabecera

El `<select>` de «Tipo» se renderizaba como `Tc`. La columna solo lleva un icono, mide
cuarenta píxeles, y el control medía lo que la celda.

Ensanchar esa columna solo movía el problema a la siguiente columna estrecha. La regla es
**ningún control de filtro hereda el ancho de su columna**: el disparador es un icono de
tamaño fijo en la cabecera y el control vive en una capa flotante, que se dimensiona por
su contenido.

De paso desaparece la segunda fila del `<thead>`, que era otra banda de controles entre
la cabecera y los datos. En el teléfono no cambia nada: ahí nunca hubo cabecera y los
filtros ya vivían apilados en `PanelDeFiltros` (§10.3).

### 12.6 — Ninguna banda existe solo para alojar botones

«Columnas» y «densidad» ocupaban una franja horizontal entera entre la cabecera de la
página y el panel. Ahora son un único menú «Opciones» —el mismo `MenuCompacto` que ya
servía al teléfono— y viven en el pie de la tabla, una barra que ya existía. Cero píxeles
de alto nuevos.

El pie también deja de mentir: con una sola página, «Por página · Página 1 de 1 · ‹ ›»
son cuatro controles que no llevan a ninguna parte. Con `last_page` a uno solo queda el
total de resultados.

Lo mismo con la cabecera de la aplicación: la miga de pan decía «Tablero» y el `<h1>` de
justo debajo decía «Tablero». La miga sirve para volver al nivel de arriba, así que solo
aparece cuando hay más de un nivel, y la cabecera baja de `h-16` a `h-12`.

### 12.7 — Los símbolos se explican

La columna de tipo son cinco iconos monocromos sin ninguna clave de lectura.

Cada icono ya declaraba `title` y `aria-label`, pero iban sobre el `<svg>`, y **un
atributo `title` sobre un `<svg>` no produce ningún globo de ayuda**: hace falta un
elemento `<title>` hijo. Ahora van sobre un `<span>` que envuelve al icono, y el globo
aparece.

El globo resuelve la duda de uno en uno. La leyenda las resuelve todas: `TablaDatos`
pinta un botón de información en la cabecera de cualquier columna para la que la página
declare un slot `leyenda-{columna}`. El contenido lo escribe la página —el componente no
sabe qué es un tipo de tarea—; el componente solo sabe que esa columna tiene leyenda.

---

## 13. Escala de acciones

Cuatro escalones. Antes había botones sólidos, botones con borde y texto pelado sin un
criterio de cuándo usar cada uno: «Editar» era `secondary` en el detalle de proyecto,
«Desactivar» era texto suelto en clientes, y «Eliminar proyecto» compartía esquina y
tamaño con «Editar».

| Escalón | Variante | Para qué | Cuántas por pantalla |
|---|---|---|---|
| **Primaria** | `default` (relleno esmeralda) | Lo que se viene a hacer a esa pantalla. | Una, o ninguna. |
| **Secundaria** | `outline` | Alternativas del mismo nivel, y todo lo que cambia estado sin destruir nada. | Las que hagan falta. |
| **Terciaria** | `ghost` | Navegación y deshacer de bajo riesgo: «Volver», «Limpiar filtros». | Las que hagan falta. |
| **Destructiva** | `destructive`, **nunca en la posición primaria** | Lo que no se puede deshacer. | Fuera del flujo principal. |

No se usa `variant="secondary"` para acciones: es un relleno gris que no ocupa ningún
escalón —ni pesa como el primario ni se lee como un control secundario— y era la mitad de
la ambigüedad. Queda para `Badge`, que no es una acción.

### 13.1 — Las destructivas no comparten prominencia

«Eliminar proyecto» estaba arriba a la derecha, del mismo tamaño que «Editar» y justo
donde va el ratón por defecto. Un proyecto arrastra módulos, tareas, periodos, pagos y
reuniones.

Baja dos escalones: vive en un menú de acciones secundarias («⋯», `outline`, tamaño
icono) y no ejecuta hasta que se **escribe el nombre del proyecto**.

### 13.2 — La confirmación es proporcional al daño

Un «¿seguro?» con dos botones se contesta que sí sin leerlo: el dedo ya iba hacia el
botón de la derecha. Escribir el nombre obliga a mirar qué se está borrando, y es la
única confirmación que distingue borrar el proyecto que tocaba de borrar el de al lado
(`components/ConfirmarPorNombre.vue`).

Se reserva para lo que arrastra otras cosas detrás. Para lo reversible basta un diálogo
normal: una confirmación que estorba en lo trivial enseña a despacharla sin leer también
en lo grave.

### 13.3 — Una acción de estado tiene forma de control y rótulo de estado

«Desactivar» en clientes era `ghost`: texto suelto alineado a la derecha, sin ninguna
apariencia de algo en lo que se pueda pulsar. Pasa a `outline`.

El rótulo dice **qué va a pasar**, no en qué estado está: en un cliente activo pone
«Desactivar» y en uno inactivo, «Activar».

---

## 14. La barra superior de la aplicación

Añadido en la fase 08. Al retirar la miga de pan en la 07d, esa franja de 48px quedó
cruzando la pantalla con el conmutador del menú a un extremo, el de tema al otro y nada en
medio: **el mismo defecto que la 07d había corregido abajo, reaparecido arriba**.

La regla no cambia —ninguna banda existe solo para alojar botones—, así que la barra
desaparece donde no tiene trabajo y se queda donde sí lo tiene:

| Ancho | Qué pasa |
|---|---|
| **A partir de `md`** | No hay barra. El conmutador del menú se va a la cabecera del propio menú lateral, que es donde se busca lo que abre y cierra el menú; plegado a iconos queda él solo, que es lo que hace falta para volver a abrirlo. |
| **Por debajo de `md`** | Se queda, y se gana el sitio: el menú es un cajón cerrado, así que esta barra es la **única puerta a la navegación** y el único sitio donde la aplicación dice cómo se llama. |

El conmutador de tema se va al **menú de usuario**, en los dos anchos. Llega igual de
rápido, se alcanza con el menú lateral plegado y no cuesta un píxel de alto. Siguen siendo
tres posiciones y no dos: con solo claro y oscuro, elegir «claro» a mediodía deja la
aplicación en claro por la noche aunque el sistema entero haya cambiado.

### 14.1 — Los controles de la tabla suben al panel

El menú «Opciones» estaba en el pie. Funcionaba, pero los controles de una tabla se buscan
arriba. Sube a una barra de estado **dentro** del panel, con el total de resultados al
lado.

No reconstruye la banda que retiró la 07d: aquella cruzaba la pantalla **fuera** del panel
y solo alojaba botones; esta va dentro del borde y lleva información. El pie queda con la
paginación, y desaparece entero cuando hay una sola página.

### 14.2 — Las columnas que miden lo que miden

`ajustada` en la API de columna de `TablaDatos`. La columna de acciones de clientes
absorbía todo el ancho sobrante —la tabla es `w-full` y el reparto proporcional se lo
llevaba—, dejando un vacío entre el último dato y unos botones pegados al canto derecho.

Para columnas de contenido fijo —acciones, badges de estado, contadores— el ancho correcto
es el de su contenido, y el sobrante se reparte entre las columnas de texto, que es donde
hace falta.

### 14.3 — Las columnas del tablero no se estiran

`items-start` en la fila de columnas. Sin eso, todas toman la altura de la más llena: un
tablero con cuarenta entregadas dejaba cinco columnas vacías de cuatro mil píxeles y la
página entera había que recorrerla para no ver nada.

Cada columna rueda por dentro con un tope. A diferencia de la tabla (§12.4), aquí **sí**
se admite una tarjeta cortada por el borde inferior: en una columna estrecha con su propia
barra de desplazamiento, la tarjeta a medias es la señal convencional de que hay más
abajo, y no la ambigüedad de una tabla que parecía haber terminado.
