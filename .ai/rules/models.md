---
paths:
  - 'app/Models/**'
  - app/Models/Tarea.php
  - app/Models/Reporte.php
  - app/Models/Reunion.php
  - app/Models/Proyecto.php
---

# Models

## Modelos: strict_types, casts tipados y relaciones con tipo de retorno
Todo modelo usa `declare(strict_types=1)`, tipa su método `casts()` y declara ahí los enums del dominio:

```php
protected function casts(): array
{
    return ['estado' => EstadoTarea::class, 'prioridad' => Prioridad::class];
}
```

Las relaciones llevan tipo de retorno explícito (`: BelongsTo`, `: HasMany`), nunca sin tipar.

## motivo_espera y motivo_cancelacion son campos distintos; entregada y cancelada son terminales
`motivo_espera` y `motivo_cancelacion` son **campos distintos y no se reutilizan entre sí**. Son cosas diferentes y se consultan en momentos diferentes; mezclarlas hace ilegible el historial.

`entregada` y `cancelada` son **terminales**: no admiten ninguna salida. Un fallo posterior a la entrega **crea una tarea nueva de tipo `correccion`**, no reabre la anterior. Así se preserva qué se entregó y cuándo, y las correcciones cuentan como trabajo real en el periodo en que ocurren.

Tampoco existe `terminada → en_espera`: `en_espera` significa exclusivamente falta de información o de recursos, y al llegar a `terminada` el commit ya está en la nube.

La matriz vive en tres sitios (enum, skill `estados-de-tarea`, `docs/diagramas/ciclo-de-vida-tarea.md`) y `tests/Unit/MatrizDeTransicionesTest.php` comprueba que coinciden. Si cambias uno, cambia los tres.

## Los .xlsx no se persisten: el snapshot es la trazabilidad
Los `.xlsx` **no se persisten en disco**: se generan bajo demanda, tanto el completo como el de cliente.

La columna `snapshot` guarda las filas exactas exportadas en el momento de generar, y es lo único que permite reconstruir un reporte histórico idéntico al que se envió. Sin ella, regenerar en noviembre el reporte de julio daría un archivo distinto y la pregunta "¿qué vio el cliente?" quedaría sin respuesta.

**No añadas una columna de ruta de archivo.** Si aparece una, alguien está guardando los Excel en disco y la trazabilidad pasó a depender de que ese archivo siga ahí.

El nombre lleva sufijo obligatorio: `{PREFIJO}_P{consecutivo}_{fecha}_completo.xlsx` y `..._cliente.xlsx`. Dos archivos con el mismo nombre en la carpeta de descargas es justo el escenario que provoca enviarle los pagos al cliente.

## La visibilidad se aplica en la policy y en el scope del listado
La visibilidad de reuniones y notas de tarea se aplica en **dos sitios a la vez**:

1. La **policy** (`ReunionPolicy@view`, `NotaTareaPolicy@view`) para el acceso por id.
2. El **scope `visiblesPara(User)`** en **todo listado**, sin excepción.

Solo con la policy, un listado sin filtrar devuelve los títulos de las reuniones internas aunque el detalle dé 403 — y "Comité interno de márgenes" ya es información filtrada. Una policy contesta sobre un objeto; un listado devuelve un conjunto y no pasa por ella.

Lo mismo vale para `Tarea::scopeVisiblesPara()`, que acota los listados a los proyectos donde el usuario tiene fila en el pivote: sin él, un filtro mal puesto devuelve tareas de proyectos ajenos.

Si añades un listado nuevo de cualquiera de los tres modelos, el scope va **antes** que cualquier filtro de `QueryBuilder`.

## `prefijo_tarea`: mayúsculas siempre, inmutable en cuanto hay códigos emitidos
El mutador de `prefijo_tarea` normaliza a mayúsculas y quita espacios (`Proyecto::normalizarPrefijo()`), y vive en el modelo para que un seeder o `tinker` tampoco puedan saltárselo.

Es **inmutable** en cuanto el proyecto emitió su primer código: `ProyectoObserver@updating` aborta el guardado con `App\Exceptions\PrefijoInmutable`. Cambiarlo dejaría sin correspondencia códigos como `MAQ-0042` que ya viajaron al cliente dentro de reportes emitidos. La alternativa de renumerar las tareas se descartó por eso mismo.

`Proyecto::tieneCodigosEmitidos()` mira `contadores_tarea.ultimo_numero`, no solo `tareas()->exists()`: borrar la tarea no borra el código que ya salió del sistema.

El `disabled` del formulario y el 422 de `ProyectoRequest` son comodidad; la cerradura es el observer.

## Fechas automáticas y vínculo de corrección
**`fecha_inicio` se fija en la PRIMERA entrada a `en_proceso` y no se sobrescribe.** Marca cuándo se empezó de verdad: volver de `en_espera` o de una revisión fallida no es empezar de nuevo, y sobrescribirla borra cuánto llevaba abierta la tarea.

**`fecha_fin` SÍ se actualiza al reentrar en `terminada`.** Si la revisión falló y hubo que retocar, la fecha honesta es la del segundo intento — la que el cliente ve en el reporte del periodo.

Las rellena `CambiarEstadoTarea`, no un formulario ni el observer.

**`tarea_origen_id`** vincula una corrección con la entrega que la originó (D3.3). Cuatro invariantes, comprobadas en `TareaObserver` además de en `TareaRequest`: solo en tipo `correccion`/`soporte`, mismo proyecto, nunca a sí misma, y sin cadenas circulares (se sube por la cadena entera, no basta mirar un eslabón). Excepción: `VinculoDeOrigenInvalido`.

Es `set null` y no `cascade`: borrar la entrega no borra la corrección, que es trabajo hecho y cuenta en su periodo.
