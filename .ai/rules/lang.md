---
paths:
  - 'lang/**'
---

# Lang

## Español es el idioma base; toda clave debe existir también en inglés
Español (`es`) es el idioma base y el fallback. Inglés (`en`) está preparado pero no traducido.

**Toda clave añadida en español debe existir también en inglés**, aunque sea sin traducir todavía (se admite el texto en español como valor provisional). Una clave que solo existe en `es` produce la clave cruda en pantalla (`periodos.cerrar`) cuando alguien cambia de idioma, y nadie lo detecta hasta que un cliente lo ve.

Los archivos viven en `lang/{es,en}/`. Si trabajas en `resources/lang/**`, aplica lo mismo.
