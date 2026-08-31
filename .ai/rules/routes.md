---
paths:
  - 'routes/**'
---

# Routes

## Ningún archivo se sirve por URL directa al disco
Todo adjunto se sirve por la ruta controlada `archivos.descargar` (`ArchivoController@show`), que evalúa `MediaPolicy` antes de emitir un byte. **Nunca enlaces a `storage/…` ni publiques una URL de disco**: un enlace público no pregunta quién lo abre.

Los respaldos `.sql` viven en el disco `local` (privado, `storage/app/private`) justamente para que esa URL no exista, y solo los alcanza `lider` o `administrador` — ni siquiera un colaborador interno, porque un dump lleva la base entera.

Al añadir rutas nuevas: las de lectura de un recurso de proyecto autorizan con `Gate::authorize('viewAny', [Modelo::class, $proyecto])`, y las de gestión de usuarios con el middleware `can:usuarios.gestionar`.
