---
paths:
  - 'docker/**'
---

# Docker

## Los límites de subida viven en cuatro sitios
El usuario sube imágenes de hasta 5 MB y respaldos .sql de hasta 250 MB. Ese límite está repartido en cuatro sitios que deben cambiarse juntos:

1. `docker/php/php.ini` → `upload_max_filesize`, `post_max_size`
2. `docker/nginx/default.conf` → `client_max_body_size`
3. Las reglas de validación de Laravel
4. Las colecciones de Medialibrary

Cambiar uno solo produce fallos silenciosos que parecen bugs de la aplicación: Nginx corta la petición antes de que PHP la vea, o PHP la descarta antes de que corra la validación. En ambos casos el error que ve el usuario no apunta al límite real.
