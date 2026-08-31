---
paths:
  - 'database/migrations/**'
---

# Migrations

## Dinero en decimal y claves foráneas con onDelete explícito
Montos `decimal(12,2)`, tasas de cambio (TRM) `decimal(12,4)`. **Nunca `float` ni `double` para dinero**: la aritmética binaria de coma flotante pierde centavos y los reportes dejan de cuadrar.

Toda clave foránea declara su comportamiento `onDelete` explícitamente (`cascadeOnDelete()`, `restrictOnDelete()`, `nullOnDelete()`). No dejes el default implícito: qué pasa al borrar un cliente con proyectos es una decisión de negocio, no un detalle del motor.
