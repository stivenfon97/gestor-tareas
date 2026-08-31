---
name: colores-y-tema
description: Usar al escribir o revisar cualquier componente Vue con color; al construir badges de estado, iconos de tipo o indicadores de prioridad; al definir tokens en el tema de Tailwind o en app.css; al implementar o corregir el modo oscuro; al elegir el color de un botón, tarjeta, tabla o columna de Kanban; y cada vez que estés a punto de escribir un hex o una utilidad de paleta como bg-amber-100. Cubre la nomenclatura de tokens semánticos, los pares claro/oscuro de cada estado, la jerarquía visual de los tres ejes y el contraste mínimo exigido.
---

# Colores y tema

## Regla que no se negocia

**Ningún componente lleva un color literal.** Ni hex (`#10b981`) ni utilidades crudas
de paleta (`bg-yellow-100`, `text-blue-600`, `border-red-500`). Solo **tokens
semánticos**.

Un literal como `bg-yellow-100` no tiene par oscuro. En modo oscuro queda texto claro
sobre fondo claro y el badge se vuelve ilegible. El token define ambos modos y el
componente no tiene que saber cuál está activo.

```vue
<!-- mal -->
<span class="bg-amber-100 text-amber-800">Pendiente</span>
<!-- bien -->
<span :class="tokenDeEstado(tarea.estado)">{{ $t(`estados.${tarea.estado}`) }}</span>
```

## Base visual

- **Marca: esmeralda.** Reservado a **acciones primarias** (botón de guardar, enlace
  principal, foco activo).
- **Base neutra: stone.** Superficies, bordes y texto.
- **Modo claro y oscuro son ambos de primera clase.** Ninguno es "el bueno".

> El esmeralda de marca **no se usa como color de estado**. Si `terminada` fuera
> esmeralda, un badge de estado sería indistinguible de un botón de acción y la gente
> haría clic en él.

## Nomenclatura de tokens

| Grupo | Patrón | Ejemplos |
|---|---|---|
| Marca | `--color-marca{-variante}` | `--color-marca`, `--color-marca-contraste` |
| Superficie | `--color-superficie{-variante}` | `--color-superficie`, `--color-superficie-elevada` |
| Borde | `--color-borde{-variante}` | `--color-borde`, `--color-borde-suave` |
| Texto | `--color-texto{-variante}` | `--color-texto`, `--color-texto-suave` |
| Estado | `--color-estado-{estado}-{rol}` | `--color-estado-pendiente-fondo`, `--color-estado-pendiente-texto` |
| Prioridad | `--color-prioridad-{prioridad}` | `--color-prioridad-critica` |

Cada estado define tres roles: `-fondo`, `-texto` y `-borde`.

## Pares claro/oscuro de cada estado

| Estado | Familia | Claro (fondo / texto) | Oscuro (fondo / texto) |
|---|---|---|---|
| `pendiente` | Ámbar | ámbar muy pálido / ámbar oscuro | ámbar oscuro desaturado / ámbar claro |
| `en_proceso` | Azul | azul muy pálido / azul oscuro | azul oscuro desaturado / azul claro |
| `en_espera` | Neutro (stone) | stone pálido / stone oscuro | stone oscuro / stone claro |
| `terminada` | Verde | verde muy pálido / verde oscuro | verde oscuro desaturado / verde claro |
| `entregada` | Morado | morado muy pálido / morado oscuro | morado oscuro desaturado / morado claro |
| `cancelada` | Rojo tenue | rojo muy pálido / rojo apagado | rojo oscuro desaturado / rojo claro apagado |

`cancelada` va **tenue a propósito**: es información de archivo, no una alarma. Si
grita más que `critica`, la pantalla miente sobre dónde mirar.

> **"Blanco" no puede existir como color de estado.** Desaparece en modo oscuro: el
> fondo ya es oscuro y un badge blanco se convierte en un bloque brillante sin
> significado. Su equivalente conceptual es el **neutro (stone)**, que sí tiene par
> claro/oscuro.

## Jerarquía visual: los tres ejes se ven distinto

Si los tres ejes llevaran relleno de color, la pantalla sería ilegible. Cada uno usa un
canal visual diferente:

| Eje | Canal visual |
|---|---|
| **Estado** | **Relleno de color.** Es el único que lo lleva |
| **Tipo** | **Icono monocromo.** Sin color propio; hereda `--color-texto-suave` |
| **Prioridad** | **Barra vertical** en el borde izquierdo de la tarjeta |

```
┌─────────────────────────────────┐
│▌ 🔧 MAQ-0042        [Pendiente] │   ▌ barra = prioridad
│  Corregir cálculo de la TRM     │   🔧 icono monocromo = tipo
└─────────────────────────────────┘   [badge] con relleno = estado
```

## El nombre del token lo da el enum, en kebab-case

`EstadoTarea::EnProceso->token()` devuelve `estado-en-proceso`, **no**
`estado-en_proceso`: el valor del enum lleva guion bajo (es lo que va en la base de
datos) pero el token lo convierte a guion medio.

No es cosmético. Tailwind v4 interpreta `_` como un espacio dentro de valores
arbitrarios, así que una clave de `@theme` con guion bajo es una fuente de sorpresas.
Los tokens quedan así:

```css
--color-estado-en-proceso-fondo
--color-estado-en-proceso-texto
--color-estado-en-proceso-borde
```

Si necesitas el nombre del token, pídeselo al enum. No lo construyas concatenando el
valor.

## Cómo se implementan los tokens en este proyecto

Verificado contra `resources/css/app.css`:

- **El modo oscuro va por clase, no por `prefers-color-scheme`.** El tema declara
  `@custom-variant dark (&:is(.dark *))` y `useAppearance.ts` alterna la clase `.dark`
  en `<html>`. Los pares claro/oscuro se definen en `:root` y se redefinen en `.dark`.
- Los tokens deben registrarse también en el bloque **`@theme inline`** para que
  Tailwind v4 genere las utilidades correspondientes (`bg-estado-pendiente-fondo`…).
- Existe ya un `Badge` de shadcn en `resources/js/components/ui/badge/`. **Compón sobre
  él** con `cn()` en lugar de escribir un `<span>` nuevo.

### Trampa: nunca construyas la clase por interpolación

El escáner de Tailwind lee el código **como texto estático**. Una clase compuesta en
tiempo de ejecución no existe para él, y la utilidad **no se genera**:

```ts
// mal: la clase no se genera. En desarrollo puede colar por el JIT;
// en el build de producción el badge sale SIN COLOR.
:class="`bg-${estado.token}-fondo`"

// bien: mapa estático con las clases escritas enteras
const clasesPorEstado: Record<EstadoTarea, string> = {
    pendiente: 'bg-estado-pendiente-fondo text-estado-pendiente-texto border-estado-pendiente-borde',
    // …los seis, literales
};
```

Esto es lo más traicionero del sistema de color: **falla solo en producción**, mucho
después de que alguien diera el componente por terminado. El `token()` del enum es la
fuente de verdad del **nombre** del token, no un generador de clases.

Da siempre un valor de reserva neutro (`en_espera`) para que un estado inesperado del
servidor no deje el badge sin estilo.

## Contraste mínimo

**4.5:1** entre texto y su fondo, en **ambos modos**, para todo texto de tamaño normal
(badges y chips incluidos).

Cómo comprobarlo:

1. Toma el par `--color-estado-{x}-texto` sobre `--color-estado-{x}-fondo`.
2. Calcula la razón de contraste (DevTools la muestra al inspeccionar el elemento; el
   selector de color señala si cumple AA).
3. Repite con el tema oscuro activo. **Un par que cumple en claro puede fallar en
   oscuro**: es el fallo más habitual y solo se ve comprobando los dos.

Si un par no llega a 4.5:1, oscurece el texto o aclara el fondo dentro de la misma
familia. No cambies de familia: el color comunica el estado.

## Antes de dar un componente por terminado

- [ ] Ningún hex ni utilidad de paleta en la plantilla.
- [ ] Se ve correcto en claro **y** en oscuro.
- [ ] Los badges de estado llegan a 4.5:1 en ambos modos.
- [ ] El esmeralda solo aparece en acciones primarias.
