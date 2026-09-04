# Documentación — Gestor de Tareas y Periodos

Herramienta de gestión de trabajo freelance: clientes, proyectos, tareas y periodos
facturables. Corre en local sobre Docker.

## Cómo leer esta documentación

Si vienes por primera vez, en este orden:

1. **[`00-entorno.md`](00-entorno.md)** — cómo levantar el proyecto.
2. **[`diagramas/contexto.md`](diagramas/contexto.md)** — qué hace el sistema y con qué habla.
3. **[`01-arquitectura.md`](01-arquitectura.md)** — cómo está construido.
4. **[`03-dominio.md`](03-dominio.md)** — las reglas de negocio que lo gobiernan.
5. **[`04-control-de-versiones.md`](04-control-de-versiones.md)** — cómo se trabaja con Git aquí.
6. **[`05-identidad-visual.md`](05-identidad-visual.md)** — si vas a tocar cualquier cosa que se vea.
7. El módulo concreto que vayas a tocar, en [`modulos/`](modulos/).
8. **[`06-historial-de-decisiones.md`](06-historial-de-decisiones.md)** — el porqué de las
   decisiones que no son obvias leyendo solo el código, y las alternativas que se
   descartaron.

## Índice

### General

| Documento | Contenido | Estado |
|---|---|---|
| [`00-entorno.md`](00-entorno.md) | Docker, servicios, volúmenes, problemas frecuentes | ✅ |
| [`01-arquitectura.md`](01-arquitectura.md) | Capas, decisiones y su porqué | ✅ |
| [`02-modelo-de-datos.md`](02-modelo-de-datos.md) | ERD y diccionario de datos | ✅ |
| [`03-dominio.md`](03-dominio.md) | Reglas de negocio e invariantes, numeradas | ✅ |
| [`04-control-de-versiones.md`](04-control-de-versiones.md) | Ramas, commits, qué se versiona y cómo revertir una fase | ✅ |
| [`05-identidad-visual.md`](05-identidad-visual.md) | Plan de diseño, autocrítica e inventario de tokens | ✅ |
| [`06-historial-de-decisiones.md`](06-historial-de-decisiones.md) | Decisiones importantes con su porqué y las alternativas descartadas | ✅ |
| [`07-despliegue.md`](07-despliegue.md) | El script `desplegar.sh`: qué valida, qué pregunta y cómo leer su informe | ✅ |

### Módulos

| Módulo | Estado | Módulo | Estado |
|---|---|---|---|
| [Autenticación](modulos/autenticacion.md) | ✅ | [Periodos](modulos/periodos.md) | ✅ |
| [Clientes](modulos/clientes.md) | ✅ | [Pagos](modulos/pagos.md) | ✅ |
| [Proyectos](modulos/proyectos.md) | ✅ | [Bitácora](modulos/bitacora.md) | ✅ |
| [Módulos](modulos/modulos.md) | ✅ | [Archivos](modulos/archivos.md) | ✅ |
| [Tareas](modulos/tareas.md) | ✅ | [Reportes](modulos/reportes.md) | ✅ |
| [Vistas guardadas](modulos/vistas.md) | ✅ | [Dashboard](modulos/dashboard.md) | ✅ |
| [Tabla de datos](modulos/tabla-de-datos.md) | ✅ | | |

### Diagramas

| Diagrama | Contenido | Estado |
|---|---|---|
| [`contexto.md`](diagramas/contexto.md) | Actores e integraciones externas | ✅ |
| [`ciclo-de-vida-tarea.md`](diagramas/ciclo-de-vida-tarea.md) | Estados y transiciones permitidas | ✅ |
| [`permisos-y-roles.md`](diagramas/permisos-y-roles.md) | Dos niveles de rol y matriz de permisos | ✅ |
| [`modelo-entidad-relacion.md`](diagramas/modelo-entidad-relacion.md) | ERD | ✅ |
| [`flujo-cambio-de-estado.md`](diagramas/flujo-cambio-de-estado.md) | Del clic al historial: quién valida y quién escribe | ✅ |
| [`flujo-cierre-periodo.md`](diagramas/flujo-cierre-periodo.md) | Cierre y pago | ✅ |
| [`flujo-generacion-reporte.md`](diagramas/flujo-generacion-reporte.md) | Emisión de los dos Excel | ✅ |

## Dónde vive cada cosa

Esta carpeta no es el único sitio con conocimiento del proyecto. Los tres mecanismos
de Boost guardan lo que los agentes necesitan **mientras programan**:

| Ruta | Qué contiene | Cuándo se carga |
|---|---|---|
| `.ai/guidelines/` | Convenciones cortas y universales, y el glosario | Siempre |
| `.ai/skills/` | Procedimientos de un dominio concreto | Bajo demanda |
| `.ai/rules/` | Decisiones y trampas atadas a rutas | Al editar un archivo que coincide |
| `docs/` | **Esto**: el porqué, para personas | Al leerlo |

Regla práctica: si es una instrucción para programar, va en `.ai/`. Si es una
explicación para entender, va en `docs/`.

### `01-arquitectura.md` no describe una fase: describe capas

Cada fase añadió su sección cuando introdujo una pieza nueva, y la fase 12 lo revisó
entero —coherencia, duplicados y secciones que el código ya había desmentido— como
último paso del proyecto. Sigue sin ser un documento "terminado" en el sentido de
cerrado: la próxima pieza que se construya sobre este proyecto le añade su sección igual
que hizo cada fase anterior.

## Mantenimiento

> **La documentación describe lo que el código hace, no lo que se planeó que hiciera.
> Si difieren, gana el código y se corrige el documento.**

Cada fase que introduce un módulo funcional rellena su archivo en `modulos/` siguiendo
la plantilla. Las secciones y su orden son obligatorios.

De todas ellas, **Decisiones de diseño** es la que da valor real: un listado de rutas
se regenera con `php artisan route:list`, pero el razonamiento detrás de una decisión
—y sobre todo las alternativas descartadas— no se recupera de ninguna parte.

Los diagramas van en **Mermaid**, dentro de los archivos Markdown. Nunca imágenes: un
PNG no se puede revisar en un diff.
