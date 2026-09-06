---
paths:
  - 'database/seeders/**'
---

# Seeders

## Los seeders cambian estados a través del servicio de transiciones, igual que la aplicación
`DesarrolloSeeder::llevarA()` cambia `estado` llamando a `App\Services\CambiarEstadoTarea`, nunca con `$tarea->update(['estado' => ...])` directo. Escribir el estado sobre el modelo se salta `ajustarFechas()` —el observer valida la transición y escribe el historial igual, así que el fallo pasa desapercibido— y produce datos que el sistema real no puede generar: fue exactamente la causa de que las tareas sembradas quedaran sin `fecha_inicio`/`fecha_fin` (fase 09, corrección 0.1). `tests/Feature/Dominio/SeederYFechasAutomaticasTest.php` lo prueba sembrando la base y comprobando que ninguna tarea `terminada`/`entregada` queda sin `fecha_fin`.

## RolesYPermisosSeeder también lo invoca una migración
Además de poder correr por `db:seed --class=RolesYPermisosSeeder` o dentro de `DatabaseSeeder`, la migración `2026_09_04_140000_sembrar_roles_y_permisos` lo invoca directamente (`(new RolesYPermisosSeeder)->run()`) desde su `up()`, para que los roles y permisos existan con solo correr `php artisan migrate`, sin depender de `--seed`.

Si cambias el namespace, el constructor, o el nombre de la clase, revisa esa migración — es una segunda llamada que no aparece si solo buscas `db:seed` o `DatabaseSeeder` en el código. Sigue siendo seguro llamarlo dos veces (idempotente por diseño), así que un entorno donde ya corrió `db:seed` a mano no gana permisos duplicados.
