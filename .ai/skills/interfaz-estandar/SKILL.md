---
name: interfaz-estandar
description: Usar SIEMPRE antes de crear o modificar cualquier pantalla, listado, tabla, cabecera, badge o botón de acción en Vue — páginas nuevas en resources/js/pages, migraciones de tablas a TablaDatos, columnas de acciones, chips de estado o rol, cambios de tema claro/oscuro y cualquier interfaz que un usuario vaya a ver. Cubre el estándar obligatorio de la aplicación: cabecera con icono de sección, listados sobre TablaDatos con búsqueda general, filtros visibles y paginación completa, acciones como iconos con tono y tooltip (BotonDeAccion), categorías como Badge con tono (lib/tonos.ts), el tema claro atenuado y cómo verificar todo con datos de volumen en claro, oscuro y móvil.
---

# Estándar de interfaz

Esto es lo que el dueño del producto pidió como norma para **todas** las pantallas,
existentes y nuevas. Una pantalla que no lo cumpla no está terminada.

Complementa, no sustituye, a `colores-y-tema` (tokens y contraste) y a
`formularios-shadcn` (campos). Léelas también si tocas color o formularios.

## 1. Cabecera de página

Toda pantalla empieza con `components/CabeceraDePagina.vue`. Nunca `Heading` para el
título de la página (queda para subtítulos de sección dentro de ella).

```vue
<CabeceraDePagina
    :icono="Users"
    :titulo="$t('gestion.usuarios.titulo')"
    :descripcion="$t('gestion.usuarios.sin_registro_publico')"
>
    <template #acciones>
        <Button as-child size="sm"><Link :href="create()">{{ $t('gestion.usuarios.crear') }}</Link></Button>
    </template>
</CabeceraDePagina>
```

- `icono` es **obligatorio** y es el mismo que la sección lleva en `AppSidebar.vue`
  (Tareas `ListChecks`, Proyectos `FolderGit2`, Clientes `Building2`, Usuarios `Users`,
  Equipos `UsersRound`, Tablero `LayoutGrid`, Periodos `CalendarRange`, Pagos
  `Banknote`, Reportes `FileSpreadsheet`, Bitácora `NotebookPen`). Una subsección puede
  llevar el suyo (Módulos `Network`, Miembros `UsersRound`).
- La descripción dice para qué sirve la pantalla en una frase.
- La acción primaria (una como mucho) va en `#acciones` con texto: es la única acción
  textual de la pantalla.

## 2. Listados: siempre `TablaDatos`, con búsqueda, filtros y paginación

Ningún listado de registros se pinta con un `<table>` a mano. Se usa
`components/TablaDatos` y el controlador le sirve su estado:

```php
$usuarios = QueryBuilder::for(User::class)
    ->allowedFilters(
        AllowedFilter::exact('activo'),
        AllowedFilter::custom('buscar', new BuscarEnColumnas(['name', 'email'])),
    )
    ->defaultSort('name')
    ->allowedSorts('name', 'email')
    ->paginate(EstadoDeTabla::porPagina($request))
    ->withQueryString();

return Inertia::render('gestion/usuarios/Index', [
    'usuarios' => $usuarios,
    'filtros' => EstadoEnLaUrl::filtros($request),
    'orden' => EstadoDeTabla::orden($request, 'name'),
    'columnasVisibles' => EstadoDeTabla::columnas($request),
]);
```

```vue
<TablaDatos
    :url="index().url"
    :filas="props.usuarios.data"
    :columnas="columnas"
    :clave="(fila) => fila.id"
    :orden="props.orden"
    :filtros="props.filtros"
    :columnas-visibles="props.columnasVisibles"
    :paginacion="props.usuarios"
    :etiqueta="$t('gestion.usuarios.titulo')"
    :busqueda="{ clave: 'buscar', marcador: $t('gestion.usuarios.buscar') }"
    :vacio="{ titulo: $t('…sin_x'), cuerpo: $t('…sin_x_ayuda') }"
    @abrir="abrir"
/>
```

Lo que el listado tiene que traer, sin excepción:

| Pieza | Cómo |
|---|---|
| **Búsqueda general** | Prop `busqueda` + `AllowedFilter::custom('buscar', new BuscarEnColumnas([...]))`. El marcador dice qué recorre («Buscar por nombre o correo…»). Las columnas que cubre ya no llevan filtro de texto propio |
| **Filtros visibles** | Cada conjunto cerrado (estado, rol, cliente…) es un `filtro: { tipo: 'opciones' }` en su columna: la tabla lo pinta como botón con nombre en la barra |
| **Orden** | `ordenPor` en las columnas que tenga sentido ordenar, igual a un `allowedSorts` |
| **Paginación** | Siempre `paginate(EstadoDeTabla::porPagina($request))`, nunca `->get()` ni un número fijo. El pie se pinta siempre |
| **Acciones** | Columna `acciones` con `ajustada: true`, `alineacion: 'derecha'` y `prioritaria: true` (para que se vea en la tarjeta de móvil) |
| **Móvil** | Una columna `principal`, las importantes `prioritaria` |
| **Vacío** | Texto de «todavía no hay» propio; el de «sin resultados» lo pone la tabla |

`BuscarEnColumnas` acepta columnas de relación con punto (`cliente.nombre`) y escapa
`%` y `_`. Si el listado acota por permisos (proyectos), la búsqueda va dentro de ese
acotado: pruébalo con un usuario que no deba ver lo ajeno.

## 3. Acciones de fila: iconos con tono y tooltip

**Nunca** un botón con texto dentro de una fila o tarjeta. Siempre
`components/BotonDeAccion.vue`:

```vue
<div class="flex items-center justify-end gap-minimo">
    <BotonDeAccion :href="show(fila.id)" :icono="Eye" :etiqueta="$t('clientes.ver')" />
    <BotonDeAccion :href="edit(fila.id)" :icono="Pencil" tono="info" :etiqueta="$t('clientes.editar')" />
    <Form v-bind="EstadoClienteController.update.form(fila.id)" v-slot="{ processing }">
        <BotonDeAccion
            type="submit"
            :disabled="processing"
            :icono="fila.activo ? PowerOff : Power"
            :tono="fila.activo ? 'aviso' : 'exito'"
            :etiqueta="fila.activo ? $t('clientes.desactivar') : $t('clientes.activar')"
        />
    </Form>
</div>
```

| Tono | Qué hace | Iconos |
|---|---|---|
| `neutro` | Ver, abrir, copiar | `Eye`, `Copy`, `ExternalLink` |
| `info` | Editar, ir a lo relacionado | `Pencil`, `ListChecks`, `Download` |
| `exito` | Activar, aprobar, reabrir | `Power`, `UserCheck`, `CircleCheck` |
| `aviso` | Desactivar, pausar, archivar | `PowerOff`, `UserX`, `Archive` |
| `peligro` | Eliminar (siempre con confirmación) | `Trash2` |

- `etiqueta` es obligatoria: es el `aria-label` y el texto del tooltip. Dice **qué va a
  pasar** («Desactivar»), no el estado actual.
- El orden es de menos a más impacto: ver → editar → cambiar estado → eliminar.
- Lo destructivo confirma en proporción al daño (`ConfirmarPorNombre.vue`).

## 4. Categorías: Badge con tono, nunca gris

Todo valor de un conjunto cerrado que se ve en una tabla o ficha es un `Badge` con la
variante que le corresponda, tomada de `resources/js/lib/tonos.ts`:

```vue
<Badge :variant="tonoDeActivo(fila.activo)">
    <span class="size-1.5 rounded-full bg-current" aria-hidden="true" />
    {{ fila.activo ? $t('…activo') : $t('…inactivo') }}
</Badge>
```

- Variantes: `exito`, `aviso`, `info`, `acento`, `peligro`, `neutro`. **No** uses
  `secondary` ni `outline` para una categoría: se leen como gris sin significado.
- Un valor nuevo (otro estado, otro rol) se añade **al mapa de `lib/tonos.ts`**, no en
  la página: así significa lo mismo en todas las pantallas.
- Estados: punto (`bg-current`). Roles y tipos: icono lucide delante.
- El estado de una **tarea** sigue con `BadgeEstado` y sus tokens `estado-*`.
- Códigos y prefijos (`AC68`, `MAQ-0042`): `Badge variant="outline"` en mono.

## 5. Tema

- El claro está atenuado a propósito (fondo 93.5%, paneles 98%): **no** reintroduzcas
  `bg-white`, `--panel: 100%` ni superficies blancas puras.
- Todo color sale de tokens (`tono-*`, `estado-*`, `texto-*`, `panel`, `borde`…). Uno
  nuevo se declara en `:root` **y** en `.dark`, y se registra en `@theme inline`.
- El esmeralda de marca es de la acción primaria; en la cabecera solo como
  `bg-marca-suave text-marca` del icono de sección.

## 6. Cómo se verifica

1. Datos de volumen en la base local (si no están):
   `docker compose exec app php artisan db:seed --class=VolumenSeeder` (~2 min, idempotente;
   cuenta de pruebas `admin@gestor.test`, contraseña la del seeder).
2. `docker compose exec node npm run build`, `npm run types:check`, `npm run lint:check`.
3. Mira la pantalla **en claro, en oscuro y a 390px**, con la tabla llena y filtrada:
   búsqueda que devuelve filas, filtro facetado abierto, tooltip de una acción, pie
   con varias páginas. Si el navegador de Claude no está disponible, Playwright del
   contenedor `humo` sirve para capturas (`docker compose --profile humo run --rm humo …`,
   con `public/hot` apartado como hace `make humo`).
4. Pruebas de feature del listado: búsqueda por cada columna que recorre, un filtro
   de opciones, orden, página siguiente y, si hay acotado por permisos, que la búsqueda
   no lo salta.

## Lista de comprobación

- [ ] `CabeceraDePagina` con el icono de la sección.
- [ ] Listado sobre `TablaDatos` con `busqueda`, filtros de opciones y paginación del servidor.
- [ ] Acciones de fila con `BotonDeAccion` (icono + tono + etiqueta), sin texto.
- [ ] Categorías con `Badge` de tono desde `lib/tonos.ts`; nada en gris.
- [ ] Sin colores literales; correcto en claro, oscuro y móvil.
- [ ] Textos en `lang/es` **y** `lang/en`.
