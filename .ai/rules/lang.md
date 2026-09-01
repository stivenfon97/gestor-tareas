---
paths:
  - 'lang/**'
---

# Lang

## Español es el idioma base; toda clave debe existir también en inglés
Español (`es`) es el idioma base y el fallback. Inglés (`en`) está preparado pero no traducido.

**Toda clave añadida en español debe existir también en inglés**, aunque sea sin traducir todavía (se admite el texto en español como valor provisional). Una clave que solo existe en `es` produce la clave cruda en pantalla (`periodos.cerrar`) cuando alguien cambia de idioma, y nadie lo detecta hasta que un cliente lo ve.

Los archivos viven en `lang/{es,en}/`. Si trabajas en `resources/lang/**`, aplica lo mismo.

## Una clave que no resuelve se pinta como su ruta y no la ve ninguna prueba de servidor
`laravel-vue-i18n` devuelve **la clave misma** cuando no encuentra la traducción, así que un fallo se pinta como texto normal: `nav.dashboard`. `npm run build` compila igual de bien y ninguna prueba de feature mira el texto. Así se cerraron dos fases en verde con la aplicación sin un solo texto.

Trampa concreta: el plugin de Vite genera `lang/php_{es,en}.json` en `buildStart` y **los borra en `buildEnd`**. `npm run build` deja sin diccionario a un dev server que ya estuviera corriendo. Lo repone `regenerarTraducciones()` en `vite.config.ts`; no lo quites.

Al añadir una clave: ponla en `lang/es` **y** en `lang/en` en el mismo commit (se admite el texto en español como valor provisional en `en`), y corre `php artisan test --filter=CoberturaDeClaves`.

## Los mensajes de validación también son traducciones, y salían como clave cruda
`lang/{es,en}/validation.php`, `passwords.php` y `pagination.php` los publica `php artisan lang:publish` y los busca **Laravel**, no nuestro código. Sin la versión en español —y con el fallback también en `es`— el validador no encuentra la línea y devuelve la clave: cada formulario rechazado mostraba `validation.required` debajo del campo. `make humo` no lo ve porque recorre pantallas, no envía formularios.

Al añadir un campo validado, añade su nombre legible en `validation.attributes`. Es lo que se interpola en `:attribute`; sin él el formulario dice «El campo contacto_email es obligatorio».

Estos tres grupos entran en la comprobación de paridad `es`/`en` de `CoberturaDeClavesTest`, pero quedan fuera de su informe de huérfanas: son ~180 claves y lo volverían ilegible.

Ojo al probar a mano: el `required` de HTML5 dispara el aviso del navegador antes de enviar, y tapa los mensajes del servidor. Para verlos hay que mandar valores que pasen el navegador y fallen la regla.
