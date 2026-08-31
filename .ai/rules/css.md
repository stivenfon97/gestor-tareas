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
