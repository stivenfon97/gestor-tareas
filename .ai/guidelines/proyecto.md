# Gestor de Tareas y Periodos

Herramienta de gestión de trabajo freelance: clientes, proyectos, tareas y periodos
facturables. Corre en local sobre Docker.

## Convenciones obligatorias

- **Todo en español**: tablas, columnas, modelos, rutas, variables, comentarios y
  textos. Excepto lo que Laravel impone (`users`, `password`, `created_at`, métodos
  del framework).
- **Vue 3 con Composition API y `<script setup>`.** Nunca Options API.
- **Los textos visibles nunca se incrustan en componentes.** Siempre desde archivos de
  traducción.
- **Ningún color literal en componentes.** Ni hex (`#10b981`) ni utilidades crudas
  (`bg-yellow-100`). Solo tokens semánticos del tema.
- **El dinero es `decimal`, nunca `float`.** Montos `decimal(12,2)`, tasas de cambio
  `decimal(12,4)`.
- **Enums de PHP para conjuntos cerrados de valores**, no tablas de catálogo.
- **Consulta `search-docs` antes de usar API de Laravel 13, Inertia 3 o Tailwind 4.**
  Son versiones recientes; los patrones memorizados de versiones anteriores fallan.
- **Ejecuta `vendor/bin/pint` antes de cerrar cualquier trabajo.**

## Glosario de dominio

Usa **exactamente** estos términos. No sinónimos, no traducciones parciales.

| Término | Significado |
|---|---|
| **Cliente** | Empresa o persona que contrata. Tiene uno o más proyectos. |
| **Proyecto** | Una aplicación o sistema. Tiene módulos, tareas y periodos propios. |
| **Módulo** | División funcional de un proyecto. Autoreferencial: puede contener submódulos. |
| **Tarea** | Unidad de trabajo. Pertenece a un proyecto, un módulo y un periodo. |
| **Estado** | Punto del ciclo de vida de una tarea. Uno a la vez. |
| **Tipo** | Naturaleza del trabajo de la tarea. No cambia. |
| **Prioridad** | Urgencia relativa. Cambia con el tiempo. |
| **Periodo** | Bloque de trabajo facturable de un proyecto. Fecha de inicio, fecha de fin y un pago. |
| **Pago** | El cobro de un periodo. Monto en USD, tasa de cambio (TRM) y monto en COP. |
| **Reporte** | Archivo Excel generado para un periodo, con registro permanente de su emisión. |
| **Bitácora** | Conjunto de reuniones registradas de un proyecto. |
| **Reunión** | Un encuentro, llamada o intercambio, con notas enriquecidas y adjuntos. |
| **Vista guardada** | Filtros, agrupación y columnas que un usuario guarda para reutilizar. |
| **Tarea arrastrada** | Tarea no terminada que pasa al siguiente periodo. Conserva `periodo_origen_id`. |

## Invariantes del dominio

- **Estado, tipo y prioridad son tres ejes independientes.** "Corrección" y "Soporte"
  son **tipos**, no estados.
- **No se registran horas en ninguna parte.** Ni por tarea ni por periodo.
- **Los periodos son por proyecto**, no por cliente. Cada uno tiene exactamente un pago
  y se cierra manualmente.
