---
paths:
  - 'resources/css/**'
---

# Css

## Tailwind 4 no ve las clases construidas en tiempo de ejecución
El escáner de Tailwind lee el código **como texto estático**. Una clase compuesta en ejecución no existe para él y la utilidad no se genera: en desarrollo puede colar por el JIT y en el build de producción el elemento sale sin estilo, mucho después de que alguien lo diera por terminado.

```ts
// mal — la utilidad no se genera
:class="`bg-${estado.token}-fondo`"

// bien — mapa con las clases escritas enteras, y reserva neutra
const clases: Record<EstadoTarea, string> = {
    pendiente: 'bg-estado-pendiente-fondo text-estado-pendiente-texto border-estado-pendiente-borde',
    // …los seis, literales
};
```

Aplica igual a la densidad de fila (`h-fila-comoda py-corto`) y a la altura de la barra de prioridad. Un token nuevo se registra en `@theme` de `resources/css/app.css`, y **se comprueba en el CSS compilado**, no en desarrollo:

```bash
docker compose exec node npm run build
grep -o 'bg-estado-[a-z-]*-fondo' public/build/assets/app-*.css | sort -u
```

Los nombres de la escala tipográfica y de espaciado (`text-cuerpo`, `px-celda`, `h-fila-comoda`) no chocan con la escala numérica de Tailwind a propósito: redefinir `--text-sm` movería de golpe las pantallas que aún no se han migrado. Inventario completo en `docs/05-identidad-visual.md`.

## El tema claro no usa blanco puro; las categorías usan tokens tono-*
El claro se atenuó a petición del dueño del producto porque deslumbraba: fondo `hsl(40 8% 93.5%)`, paneles a 98%, menú lateral un escalón por debajo del fondo. No vuelvas a `--panel: 100%` ni a `bg-white`.
Los tonos de categoría (`tono-exito|aviso|info|acento|peligro|neutro` con `-fondo`, `-texto`, `-borde` y `-solido`) se apoyan en las familias de los estados y los consumen las variantes de `Badge` y `BotonDeAccion`. `-solido` es el color de un icono suelto sobre el panel (≥3:1); `-texto` va sobre `-fondo` (≥4.5:1). Todo token nuevo, en `:root`, `.dark` y `@theme inline`.
