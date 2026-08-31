---
name: archivos-y-adjuntos
description: Usar al adjuntar, subir, servir o borrar archivos; al registrar colecciones o conversiones de spatie/laravel-medialibrary; al configurar discos de filesystem; al escribir validación de tamaño o tipo de archivo subido; al construir rutas de descarga de imágenes, documentos o respaldos .sql; y al cambiar cualquier límite de tamaño de subida. Cubre las tres colecciones y sus límites, por qué solo las imágenes llevan conversiones, el disco privado de los respaldos y los cuatro sitios que deben cambiarse juntos.
---

# Archivos y adjuntos

Gestión de adjuntos con `spatie/laravel-medialibrary` sobre disco local.

## Las tres colecciones

| Colección | Límite | Disco | Conversiones |
|---|---|---|---|
| `imagenes` | **5 MB** | público | **Sí** |
| `documentos` | **20 MB** | público | No |
| `respaldos_bd` | **256 MB** | **privado** | **No** |

## Solo `imagenes` registra conversiones

**Nunca registres conversiones en `documentos` ni en `respaldos_bd`.**

Medialibrary aplica las conversiones registradas a todo lo que entra en la colección.
Un dump `.sql` de 250 MB pasando por procesamiento de imagen intenta cargarlo entero en
memoria como si fuera un mapa de bits: revienta el `memory_limit`, tumba el worker y
deja el job reintentando tres veces sobre el mismo archivo.

Registra las conversiones **por colección**, nunca globalmente:

```php
$this->addMediaCollection('imagenes')
    ->registerMediaConversions(function (Media $media): void {
        $this->addMediaConversion('miniatura')->width(320);
    });
```

Las conversiones las procesa el contenedor `worker` por la cola.

## Los respaldos van a disco privado

`respaldos_bd` usa el **disco privado**, jamás el público. Un `.sql` contiene los datos
de producción del cliente; en el disco público queda accesible por URL directa a
cualquiera que la adivine o la reciba reenviada.

La descarga se sirve **solo por una ruta protegida con Policy** que comprueba el acceso
antes de devolver el archivo. Nunca enlaces directos, nunca URLs firmadas de larga
duración.

```php
// mal: expone la ruta pública
return $media->getUrl();
// bien: autorización antes de servir
$this->authorize('descargar', $respaldo);
return response()->download($media->getPath(), $media->file_name);
```

## Los límites de tamaño viven en cuatro sitios

Cambiar uno solo produce fallos silenciosos que parecen bugs de la aplicación:

| # | Sitio | Qué controla |
|---|---|---|
| 1 | `docker/php/php.ini` | `upload_max_filesize`, `post_max_size` |
| 2 | `docker/nginx/default.conf` | `client_max_body_size` |
| 3 | Reglas de validación de Laravel | El límite por colección |
| 4 | Colecciones de Medialibrary | El límite declarado en el modelo |

El orden en que fallan importa para diagnosticar:

- Si **Nginx** es el más bajo, corta la petición **antes de que PHP la vea**: el
  usuario recibe un 413 y en los logs de Laravel no hay absolutamente nada.
- Si **PHP** es el más bajo, descarta el cuerpo **antes de que corra la validación**:
  llega una request con `$_FILES` vacío y el mensaje de error habla de un campo
  requerido, no del tamaño.

En ambos casos el error que ve el usuario no menciona el límite real. Al tocar
cualquiera de los cuatro, revisa los otros tres.

## En la base de datos solo hay rutas y metadatos

La tabla `media` guarda nombre, MIME, tamaño, disco y ruta. **Los binarios viven en el
volumen Docker `archivos-storage`**, montado en `/var/www/html/storage/app`.

Consecuencias que hay que tener presentes:

- Un `docker compose down -v` **destruye los archivos**. `down` a secas no.
- Un respaldo de la base de datos **no incluye los adjuntos**. Restaurar solo el SQL
  deja filas en `media` apuntando a archivos que ya no existen.
- Nunca guardes contenido de archivo en una columna. Ni siquiera "solo este pequeño".
