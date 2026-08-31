---
paths:
  - 'app/Enums/**'
---

# Enums

## Enums exponen etiqueta() y token(), nunca colores
Cada enum de dominio expone dos métodos:

- `etiqueta(): string` — el texto traducible que se muestra al usuario.
- `token(): string` — el nombre del token de color semántico (por ejemplo `estado-pendiente`).

`token()` **nunca** devuelve un hex ni una clase de Tailwind. Devuelve el nombre del token; el tema decide el color y garantiza el par claro/oscuro. Si un enum devuelve `#f59e0b` o `bg-amber-100`, está mal.
