---
paths:
  - 'resources/js/**/*.vue'
  - 'resources/js/**'
---

# Formularios

## Todo campo se compone con shadcn-vue: Label, InputGroup con icono lucide e InputError
El sistema de diseño es **shadcn-vue con el preset Emerald** (Style Nova, Base color Neutral, Theme Emerald, Lucide, Radius Default). Un campo de texto **no** es un `<input>` con clases: es este bloque, y nada más.

```vue
<Field>
    <Label for="email">{{ $t('auth.acceso.correo') }}</Label>
    <InputGroup>
        <InputGroupInput id="email" type="email" :placeholder="$t('auth.acceso.correo_marcador')" :aria-invalid="!!errors.email" />
        <InputGroupAddon><Mail aria-hidden="true" /></InputGroupAddon>
    </InputGroup>
    <InputError :message="errors.email" />
</Field>
```

- `Label` siempre, asociado por `for`/`id`: el placeholder no es una etiqueta.
- Icono de `@lucide/vue` al inicio según el tipo de dato (correo `Mail`, contraseña `Lock`, búsqueda `Search`, fecha `Calendar`, dinero `DollarSign`/`Banknote`…). Tabla completa en `.ai/skills/formularios-shadcn/SKILL.md`.
- Botón interno (ver contraseña, limpiar) con `InputGroupButton type="button"` dentro de `InputGroupAddon align="inline-end"`, con `aria-label` traducido.
- Texto largo con `InputGroupTextarea`; listas cerradas con `Select`/`SelectTrigger`.

## Los estados se ponen con atributos, nunca con clases
Foco, error y éxito los pinta el componente desde tokens del tema. La página solo pone el atributo:

| Estado | Atributo en el control | Token |
|---|---|---|
| Foco | — (`:focus-visible`) | `--campo-foco`: azul en claro, gris en oscuro |
| Error | `:aria-invalid="!!errors.x"` | `--destructive` |
| Éxito comprobado | `:data-valido="true"` | `--exito` |

Prohibido escribir en una página `focus:border-*`, `ring-*`, `border-red-*` o cualquier `<style>` para un campo. Razón: el foco de campo es azul/gris y el de los botones es esmeralda; un parche local rompe uno de los dos modos y no se ve hasta cambiar de tema. Si falta un estado, se añade como token en `resources/css/app.css` (registrado en `@theme inline`) y se aplica en el componente de `components/ui/`.

## Formularios en tarjeta por secciones y validación en vivo, nunca la del navegador
Lo pidió el dueño del producto: los globos nativos del navegador «son horribles».
- Todo `<Form>`/`<form>` lleva `novalidate`. La validación es la del FormRequest, en vivo con Precognition de Inertia 3 (`HandlePrecognitiveRequests` en el grupo `web`): `@change="validate('campo')"` + `:data-valido="valid('campo')"` con `:validation-timeout="500"`, o `useForm(...).withPrecognition(...).setValidationTimeout(500)`.
- Altas y ediciones usan `components/formularios/`: `TarjetaDeFormulario` > `SeccionDeFormulario` (título y ayuda en `{modulo}.formulario.*`) > `Field`, y `PieDeFormulario` en `#pie`. Campos cortos emparejados en `grid gap-bloque md:grid-cols-2`.
Detalle en la skill `interfaz-estandar` §2c y §2d.

## Formularios a todo el ancho e icono del textarea en línea
El dueño del producto los vio estrechos y con un salto de línea entre el icono y el placeholder.
- Ninguna página de formulario lleva `max-w-*` en su contenedor raíz y `TarjetaDeFormulario` no tiene tope: el layout ya acota a `max-w-contenido`. Tres campos cortos de la misma familia van en `md:grid-cols-2 lg:grid-cols-3`.
- Un `InputGroupTextarea` lleva su icono con `InputGroupAddon` sin `align` (inline-start): `ui/input-group` lo alinea arriba, en la primera línea, cuando el grupo contiene un textarea. Prohibido `align="block-start"` en textareas.

## Un tamaño de texto nuevo en el tema se registra también en cn()
`cn()` (`lib/utils.ts`) usa `extendTailwindMerge` con la escala `--text-*` del tema (micro, fino, cuerpo, medio, alto, cima). Sin eso, tailwind-merge toma `text-micro` por un color y lo descarta en cuanto llega un `text-<token>-texto`, y el tamaño desaparece sin aviso (así salían los badges de estado «pequeños» a tamaño normal). Si añades un `--text-*` a app.css, añádelo a esa lista.

## Ningún comentario HTML dentro del slot #item de vuedraggable
vuedraggable exige que `<template #item>` tenga un solo hijo. En desarrollo Vue conserva los comentarios `<!-- -->` como nodos, así que un comentario dentro del slot rompe la lista con «Item slot must have only one child» (traza en rojo dentro del contenedor). En el build de producción los comentarios se eliminan y no falla: por eso pasa `make humo` y las capturas sobre el build. El comentario va encima del `<draggable>`. Pasó en `proyectos/Modulos.vue`.

## Lista que sale de registros = SelectorConBusqueda; conjunto cerrado = Select
Todo desplegable cuyas opciones salen de registros (clientes, personas, equipos, módulos, periodos, tareas, miembros) usa `components/SelectorConBusqueda.vue`, aunque hoy tenga tres opciones: crecen con el uso y un `Select` largo obliga a hacer scroll a ciegas (pedido del dueño del producto). Los conjuntos cerrados de un enum (estado, tipo, prioridad, rol, visibilidad, agrupación, origen de la TRM, tamaño de página) siguen en `Select`.
- Se apoya en `ui/combobox` (reka-ui). Filtro propio sin tildes ni mayúsculas; busca también en `detalle` (correo, estado). Pinta 100 como máximo y avisa de que hay más.
- `nula` = opción «ninguno/todos», que devuelve `null`. `name` añade un input oculto para los `<Form>` de Inertia (es controlado: dale un `ref`).
- En `TablaDatos`, `FiltroDeColumna` y `FiltroFacetado` pasan a buscador a partir de 8 opciones.
- Trampa: sin `:display-value="() => ''"` en `ComboboxInput`, reka-ui rellena el buscador con el id elegido y lo escrito se pega detrás («96factur»).
