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
