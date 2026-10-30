---
name: formularios-shadcn
description: Usar al construir, migrar o revisar cualquier formulario o campo en Vue — Input, Textarea, Select, Checkbox, buscadores, filtros, diálogos de captura — y siempre que estés a punto de escribir un <input> o <textarea> a mano, una clase de foco o de borde para un campo, o un icono dentro de un campo. Cubre la composición obligatoria con Label + InputGroup + InputGroupAddon (icono lucide) + InputGroupInput + InputError, qué icono va con qué tipo de campo, los estados de foco (azul en claro, gris en oscuro), error (aria-invalid → destructive) y éxito (data-valido → exito), y la prohibición de inventar CSS fuera de shadcn-vue y los tokens del tema.
---

# Formularios con shadcn-vue

## Sistema de diseño base

Toda la interfaz se construye con **shadcn-vue** sobre el preset del proyecto:

| Opción del preset | Valor |
|---|---|
| Style | Nova (componentes del registro `new-york-v4`, ver `components.json`) |
| Base color | Neutral |
| Theme | **Emerald** (la marca, `--marca`) |
| Icon library | **Lucide** (`@lucide/vue`) |
| Radius | Default (`--radius: 0.625rem`: campos `rounded-md` 8px, paneles `rounded-lg` 10px) |

Los componentes viven en `resources/js/components/ui/` y se añaden con el registro
oficial de shadcn-vue, nunca reescritos de memoria. Lo único que el proyecto les cambia
son **tokens**: el foco de campo (`campo-foco`) y el estado de éxito (`exito`).

> **Trampa del CLI.** `npx shadcn-vue@latest add …` en este proyecto deja
> `import { cn } from "cn"` sin resolver, instala un paquete npm llamado `cn` que no
> tiene nada que ver, sube versiones de `reka-ui` y `@lucide/vue`, y se para pidiendo
> sobrescribir `button`/`input`. Si lo usas, revisa `git diff package.json` y los
> imports antes de seguir. Alternativa fiable: descargar
> `https://www.shadcn-vue.com/r/styles/new-york-v4/<componente>.json` y copiar sus
> `files[].content` cambiando `"cn"` por `"@/lib/utils"` y
> `@/registry/new-york-v4/ui/*` por `@/components/ui/*`.

## Regla que no se negocia: nada de CSS inventado

En un campo **solo** van componentes de shadcn-vue, utilidades de Tailwind que esos
componentes ya usan y tokens del tema. Prohibido:

- `<input>`, `<textarea>` o `<select>` nativos con clases propias.
- Clases de foco, borde o anillo escritas a mano en la página (`focus:border-blue-500`,
  `ring-green-500`, `border-red-500`…). El estado se expresa con **atributos**
  (`aria-invalid`, `data-valido`) y el componente ya sabe pintarlo.
- Colores de paleta (`text-blue-600`) o hex: ver la skill `colores-y-tema`.
- `<style>` en el componente o reglas nuevas en `app.css` para un campo concreto.

Si un campo necesita algo que shadcn-vue no da, se añade el componente oficial que lo
da (o un token nuevo en `app.css`, registrado en `@theme inline`), no un parche local.

## Anatomía obligatoria de un campo

```
<div class="grid gap-2">              ← un bloque por campo
  <Label for="x">                     ← SIEMPRE, asociado por for/id
  <InputGroup>                        ← el borde, el radio y los estados
    <InputGroupInput id="x" …>        ← el control (o InputGroupTextarea)
    <InputGroupAddon>                 ← icono lucide al inicio (align por defecto)
      <Icono />
    </InputGroupAddon>
    <InputGroupAddon align="inline-end">   ← opcional: botón interno
      <InputGroupButton …>
    </InputGroupAddon>
  </InputGroup>
  <InputError :message="errors.x" />  ← debajo, siempre presente
</div>
```

1. **`Label` siempre**, con `for` igual al `id` del control. El placeholder **no
   sustituye** a la etiqueta: desaparece al escribir y el lector de pantalla no lo
   anuncia como nombre.
2. **`InputGroup` + `InputGroupAddon` con icono lucide al inicio.** El icono acompaña al
   placeholder y dice de un vistazo qué tipo de dato va ahí. Es decorativo: lleva
   `aria-hidden="true"`. El orden en la plantilla da igual: `InputGroupAddon` se coloca
   con `order-first`/`order-last` según `align`; ponerlo después del control mantiene el
   orden de tabulación natural.
3. **`InputGroupInput`** para una línea, **`InputGroupTextarea`** para texto largo
   (con `align="block-start"` en el addon si el icono va encima).
4. **Botón interno con `InputGroupButton`** dentro de un `InputGroupAddon
   align="inline-end"`: ver contraseña, limpiar búsqueda, copiar. Variante `ghost` y
   tamaño `icon-xs` por defecto; siempre `type="button"` y `aria-label` traducido.
5. **`InputError`** (`@/components/InputError.vue`) justo debajo, con el mensaje del
   servidor.
6. Todos los textos (`Label`, `placeholder`, `aria-label`) salen de `$t(...)`: la regla
   `vue/no-bare-strings-in-template` de ESLint revisa también `placeholder` y
   `aria-label`.

Un `Input` suelto (sin grupo) solo se admite donde no hay sitio para el icono: celdas de
tabla editables y controles de una barra de herramientas ya rotulada. Un `Select` usa
`SelectTrigger` (no hay `InputGroup` para él) con su `Label` y su `InputError`.

## Qué icono va con qué campo

Siempre de `@lucide/vue`, a tamaño por defecto (el addon lo fija en `size-4`).

| Tipo de campo | Icono |
|---|---|
| Correo electrónico | `Mail` |
| Contraseña | `Lock` (y `Eye` / `EyeOff` en el botón interno) |
| Búsqueda / filtro de texto | `Search` |
| Nombre de persona, usuario | `User` |
| Varios usuarios, equipo, miembros | `Users` |
| Fecha | `Calendar` (rango: `CalendarDays`) |
| Hora | `Clock` |
| Dinero en USD | `DollarSign` |
| Dinero en COP, montos genéricos | `Banknote` |
| Tasa de cambio (TRM) | `ArrowLeftRight` |
| Porcentaje | `Percent` |
| Teléfono | `Phone` |
| URL, enlace | `Link` (dominio o sitio: `Globe`) |
| Texto corto genérico (título, asunto) | `Type` |
| Texto largo, descripción, notas | `FileText` (o `AlignLeft`) |
| Número, cantidad, código | `Hash` |
| Etiqueta, prefijo de proyecto | `Tag` |
| Empresa, cliente | `Building2` |
| Proyecto | `FolderKanban` |
| Módulo | `Layers` |
| Dirección, lugar | `MapPin` |
| Token, clave de API | `KeyRound` |

Si el tipo no está en la tabla, elige el icono de lucide más literal y añádelo aquí. No
uses el mismo icono para dos tipos distintos en un mismo formulario.

## Estados: foco, error y éxito

Los cuatro componentes de campo (`Input`, `InputGroup`, `Textarea`, `SelectTrigger`) y
`Checkbox` (solo foco y error) pintan los estados desde tokens. La página **no añade
clases**: pone el atributo.

| Estado | Cómo se activa | Claro | Oscuro | Token / clases del componente |
|---|---|---|---|---|
| Reposo | — | borde `--input` | borde `--input` | `border-input` |
| **Foco** | teclado o clic (`:focus-visible`) | **azul** | **gris neutro** | `--campo-foco` → `focus-visible:border-campo-foco focus-visible:ring-campo-foco/50` |
| **Error** | `:aria-invalid="!!errors.x"` en el control | rojo | rojo | `--destructive` → `aria-invalid:border-destructive aria-invalid:ring-destructive/20` |
| **Éxito** | `:data-valido="…"` en el control | verde | verde claro | `--exito` → `data-[valido=true]:border-exito data-[valido=true]:ring-exito/20` |

- En un `InputGroup` el atributo va en el **control** (`InputGroupInput`,
  `InputGroupTextarea`); el grupo lo detecta con `has-[…]` y pinta el borde entero.
- `aria-invalid="false"` no significa «válido»: es la ausencia de error. El éxito se
  marca solo cuando algo lo ha comprobado de verdad (validación en vivo, disponibilidad
  de un nombre), con `data-valido="true"`. No pongas verde a todos los campos rellenos.
- Error y éxito ganan al foco: un campo con error sigue rojo aunque esté enfocado.
- El foco de campo **no es esmeralda**: el esmeralda (`--ring`, `--marca`) es de los
  botones y enlaces primarios. Un campo enfocado no es una acción.
- El verde de `--exito` tampoco es esmeralda, por la misma razón.

## Ejemplo completo

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Eye, EyeOff, Lock, Mail } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/login';

const verContrasena = ref(false);
</script>

<template>
    <Form v-bind="store.form()" v-slot="{ errors }" class="grid gap-6">
        <div class="grid gap-2">
            <Label for="email">{{ $t('auth.acceso.correo') }}</Label>
            <InputGroup>
                <InputGroupInput
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    required
                    :placeholder="$t('auth.acceso.correo_marcador')"
                    :aria-invalid="!!errors.email"
                />
                <InputGroupAddon>
                    <Mail aria-hidden="true" />
                </InputGroupAddon>
            </InputGroup>
            <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
            <Label for="password">{{ $t('auth.acceso.contrasena') }}</Label>
            <InputGroup>
                <InputGroupInput
                    id="password"
                    :type="verContrasena ? 'text' : 'password'"
                    name="password"
                    autocomplete="current-password"
                    required
                    :placeholder="$t('auth.acceso.contrasena_marcador')"
                    :aria-invalid="!!errors.password"
                />
                <InputGroupAddon>
                    <Lock aria-hidden="true" />
                </InputGroupAddon>
                <InputGroupAddon align="inline-end">
                    <!-- La clave de aria-label es de ejemplo: créala en lang/ -->
                    <InputGroupButton
                        type="button"
                        size="icon-xs"
                        :aria-label="
                            $t(
                                verContrasena
                                    ? 'comun.ocultar_contrasena'
                                    : 'comun.mostrar_contrasena',
                            )
                        "
                        :aria-pressed="verContrasena"
                        @click="verContrasena = !verContrasena"
                    >
                        <EyeOff v-if="verContrasena" aria-hidden="true" />
                        <Eye v-else aria-hidden="true" />
                    </InputGroupButton>
                </InputGroupAddon>
            </InputGroup>
            <InputError :message="errors.password" />
        </div>
    </Form>
</template>
```

Éxito comprobado en vivo (p. ej. un prefijo de proyecto libre):

```vue
<InputGroupInput
    id="prefijo"
    v-model="prefijo"
    :aria-invalid="prefijoOcupado"
    :data-valido="prefijoComprobado && !prefijoOcupado"
/>
```

## Antes de dar un formulario por terminado

- [ ] Cada control tiene `Label` con `for` = `id`.
- [ ] Cada campo de texto va en `InputGroup` con su icono lucide al inicio
      (`aria-hidden="true"`), salvo las excepciones de tabla/barra.
- [ ] `InputError` debajo de cada campo y `:aria-invalid` enlazado al error.
- [ ] Ninguna clase de color, borde, foco o anillo escrita en la página.
- [ ] Probado en claro (foco azul) **y** en oscuro (foco gris), con un error forzado.
- [ ] Los botones internos son `InputGroupButton type="button"` con `aria-label`
      traducido.
