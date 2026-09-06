---
paths:
  - 'database/migrations/**'
---

# Migrations

## Dinero en decimal y claves foráneas con onDelete explícito
Montos `decimal(12,2)`, tasas de cambio (TRM) `decimal(12,4)`. **Nunca `float` ni `double` para dinero**: la aritmética binaria de coma flotante pierde centavos y los reportes dejan de cuadrar.

Toda clave foránea declara su comportamiento `onDelete` explícitamente (`cascadeOnDelete()`, `restrictOnDelete()`, `nullOnDelete()`). No dejes el default implícito: qué pasa al borrar un cliente con proyectos es una decisión de negocio, no un detalle del motor.

## Datos de referencia idempotentes (roles, permisos) se siembran desde una migración
Cuando un dato es infraestructura del dominio —no un dato de ejemplo— y el sistema no funciona sin él (como los roles `administrador`/`miembro`/`invitado`), invócalo desde una migración (`up()`), no solo desde un seeder que dependa de `--seed`. `php artisan migrate` es lo único que cualquier forma de desplegar el proyecto corre siempre; un seeder aparte depende de que alguien recuerde una bandera o un nombre de clase.

Ejemplo: `2026_09_04_140000_sembrar_roles_y_permisos` llama a `(new RolesYPermisosSeeder)->run()`. El seeder debe ser idempotente (`findOrCreate`, `syncPermissions`) para que correr la migración no duplique nada si alguien ya sembró a mano.

`down()` no revierte el sembrado: borrar roles/permisos ya asignados en cualquier entorno donde la migración ya corrió sería un efecto secundario más grave que lo que un rollback normalmente implica.
