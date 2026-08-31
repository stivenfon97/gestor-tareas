---
paths:
  - 'config/**'
---

# Config

## Zona horaria America/Bogota y locale es
La aplicación corre con `APP_TIMEZONE=America/Bogota` y `APP_LOCALE=es` (fallback `es`, inglés preparado pero no activo). Las fechas se guardan y se muestran en esa zona.

Los textos visibles nunca van incrustados en los componentes: van a archivos de traducción desde el primer día.
