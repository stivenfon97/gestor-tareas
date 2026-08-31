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

**Qué se hizo.** **Se prohíbe en toda la aplicación menos en un sitio: las cabeceras
`<th>` de tabla.** Ahí sí se justifica, y es el único caso donde la justificación es
real: la cabecera tiene que distinguirse del cuerpo a 11px, y las dos alternativas son
peores —subirle el peso la hace competir con el nombre de la tarea, y ponerle relleno de
color choca con la regla de que solo el estado lleva relleno—. La caja alta hace ese
trabajo sin gastar ni peso ni color. Las etiquetas de sección van en `--text-fino` con
peso 500 y caja mixta.

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
