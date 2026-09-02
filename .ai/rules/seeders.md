---
paths:
  - 'database/seeders/**'
---

# Seeders

## Los seeders cambian estados a través del servicio de transiciones, igual que la aplicación
`DesarrolloSeeder::llevarA()` cambia `estado` llamando a `App\Services\CambiarEstadoTarea`, nunca con `$tarea->update(['estado' => ...])` directo. Escribir el estado sobre el modelo se salta `ajustarFechas()` —el observer valida la transición y escribe el historial igual, así que el fallo pasa desapercibido— y produce datos que el sistema real no puede generar: fue exactamente la causa de que las tareas sembradas quedaran sin `fecha_inicio`/`fecha_fin` (fase 09, corrección 0.1). `tests/Feature/Dominio/SeederYFechasAutomaticasTest.php` lo prueba sembrando la base y comprobando que ninguna tarea `terminada`/`entregada` queda sin `fecha_fin`.
