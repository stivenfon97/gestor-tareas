---
paths:
  - 'tests/**'
---

# Tests

## Toda ruta que modifica datos tiene prueba; las transiciones inválidas se prueban explícitamente
Cada endpoint que modifique datos necesita una prueba de feature en Pest.

Las **transiciones de estado inválidas se prueban explícitamente**, comprobando que se rechazan — no basta con probar el camino feliz. Una transición prohibida que nadie prueba es una que acabará permitiéndose sin que nadie lo note:

```php
it('rechaza pasar de cancelada a en_proceso', function () {
    $tarea = Tarea::factory()->cancelada()->create();
    $this->patch(route('tareas.estado', $tarea), ['estado' => 'en_proceso'])
        ->assertForbidden();
    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Cancelada);
});
```

La generación de cada Excel necesita una prueba que valide la **estructura del archivo**, no solo que la respuesta sea 200. Sin pruebas de navegador.

## «Sin pruebas de navegador» tiene una excepción acotada: `make humo`
La regla sigue en pie para todo lo demás. La excepción es `tests/humo/`, que con Playwright recorre cinco pantallas a tres anchos (360, 768, 1440) en claro y oscuro y comprueba **dos cosas y nada más**: que no queda texto con forma de clave de traducción y que el documento no desborda a lo ancho.

Existe porque esas dos son invisibles para el servidor: `npm run build` compila igual de bien con las claves sin resolver y una tabla que se corta pasa todas las pruebas de Pest.

**No es una suite end-to-end y no debe crecer hasta serlo.** No añadas ahí flujos, formularios ni aserciones de negocio: eso es Pest. Va aparte de `php artisan test` (`make humo`) porque tarda y necesita los servicios levantados.

Corre contra el build, no contra el dev server: la URL de `public/hot` es `localhost:5173`, que dentro de la red de Compose es el propio contenedor. La sesión se abre una sola vez en `globalSetup` porque Fortify limita los intentos de acceso.
