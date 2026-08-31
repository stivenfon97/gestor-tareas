---
name: estados-de-tarea
description: Usar al crear, modificar o validar transiciones de estado de tareas; al escribir el Observer de historial de estados; al construir badges o chips de estado; al filtrar o agrupar tareas por estado, tipo o prioridad; al escribir el enum EstadoTarea, TipoTarea o PrioridadTarea; al mover tarjetas en el Kanban; y al decidir si un valor nuevo es un estado o un tipo. Cubre la matriz de once transiciones permitidas, los campos obligatorios de en_espera y cancelada, y la independencia de los tres ejes de clasificación.
---

# Estados de tarea

## Los tres ejes son independientes

Una tarea se clasifica en **tres columnas que no se mezclan**:

| Eje | Valores | Cambia |
|---|---|---|
| **Estado** | `pendiente`, `en_proceso`, `en_espera`, `terminada`, `entregada`, `cancelada` | Sí, por transiciones |
| **Tipo** | `desarrollo`, `correccion`, `soporte`, `mejora`, `investigacion` | **No.** Se fija al crear |
| **Prioridad** | `baja`, `media`, `alta`, `critica` | Sí, libremente |

> **"Corrección" y "Soporte" son TIPOS, no estados.** Recorren el mismo ciclo de vida
> que cualquier otra tarea: una corrección puede estar `pendiente`, `en_proceso` o
> `entregada`. Convertirlos en estados es el error más frecuente en este dominio y
> rompe la matriz de transiciones.

## Los seis estados

| Estado | Significado |
|---|---|
| `pendiente` | Creada, sin empezar |
| `en_proceso` | Trabajándose ahora |
| `en_espera` | Bloqueada por falta de información o de recursos |
| `terminada` | Lista en desarrollo, sin desplegar |
| `entregada` | Desplegada y confirmada por el cliente |
| `cancelada` | Descartada |

### Qué significa exactamente `en_espera`

**Falta de información o de recursos para entender o ejecutar la tarea, y nada más.**
No es un cajón de sastre para cualquier demora.

En concreto, **no cubre esperas de despliegue**: cuando una tarea llega a `terminada`
el commit ya está en la nube, y bajarlo al servidor es trámite, no bloqueo. Por eso
**no existe la transición `terminada → en_espera`**, y añadirla convertiría `en_espera`
en un estado que no significa nada.

## Matriz de transiciones permitidas

Filas = estado actual. Columnas = estado destino. `✔` = permitida, `✘` = prohibida,
`—` = el mismo estado (no es una transición).

| Desde ↓ / Hacia → | pendiente | en_proceso | en_espera | terminada | entregada | cancelada |
|---|---|---|---|---|---|---|
| **pendiente** | — | ✔ | ✔ | ✘ | ✘ | ✔ |
| **en_proceso** | ✘ | — | ✔ | ✔ | ✘ | ✔ |
| **en_espera** | ✔ | ✔ | — | ✘ | ✘ | ✔ |
| **terminada** | ✘ | ✔ | ✘ | — | ✔ | ✘ |
| **entregada** | ✘ | ✘ | ✘ | ✘ | — | ✘ |
| **cancelada** | ✘ | ✘ | ✘ | ✘ | ✘ | — |

Once transiciones válidas. La fuente de verdad ejecutable es
`EstadoTarea::transicionesPermitidas()`; `tests/Unit/MatrizDeTransicionesTest.php`
comprueba que esta tabla, la del diagrama y el enum dicen exactamente lo mismo.

Lectura en prosa:

- `pendiente` → `en_proceso` (se empieza), `en_espera` (el cliente nunca explicó qué
  quería, así que está bloqueada sin haberse empezado) o `cancelada`.
- `en_proceso` → `en_espera` (se bloquea), `terminada` (se acaba) o `cancelada`.
- `en_espera` → `pendiente` (llegó la información y vuelve a la cola), `en_proceso` (se
  desbloquea y se retoma) o `cancelada`.
- `terminada` → `entregada` (se despliega y el cliente confirma) o `en_proceso` (falló
  la revisión y hay que retocar). **No se cancela lo que ya está hecho**: si no se va a
  entregar, se entrega igual o se abre una tarea nueva que lo revierta.
- `entregada` es **terminal**. Un fallo posterior no reabre la tarea: se crea una
  **tarea nueva de tipo `correccion`**. Así se preserva qué se entregó y cuándo, y las
  correcciones cuentan como trabajo real en el periodo en que ocurren.
- `cancelada` es **terminal**. Cancelar por error obliga a crear otra tarea. Es
  deliberado: el estado registra una decisión del cliente, no un borrador.

Una transición de un estado a sí mismo no es una transición y no se registra.

## Campos obligatorios en dos transiciones

- Pasar a **`en_espera` exige `motivo_espera`**. Sin él la transición se rechaza. Una
  tarea bloqueada sin motivo registrado es una tarea que nadie sabe cómo desbloquear.
- Pasar a **`cancelada` exige `motivo_cancelacion`**, que es un **campo propio y
  distinto** de `motivo_espera`. Son cosas diferentes y se consultan en momentos
  diferentes: mezclarlas hace ilegible el historial.

Ambos se limpian cuando la tarea sale de ese estado, pero **el texto queda en el
historial de estados**, así que no se pierde.

## El historial lo escribe un Observer, no el controlador

Todo cambio de estado se registra mediante `TareaObserver`:

```php
public function updating(Tarea $tarea): void
{
    if (! $tarea->isDirty('estado')) {
        return;
    }

    // Valida contra la matriz, exige el motivo del destino y limpia el del
    // estado que se abandona. Todo esto debe pasar ANTES del UPDATE.
}

public function updated(Tarea $tarea): void
{
    if ($tarea->wasChanged('estado')) {
        // Escribir el registro en historial_estados.
    }
}
```

**La validación va en `updating` y la escritura del historial en `updated.`** El
registro histórico se escribe solo cuando el UPDATE ya se ejecutó: hacerlo en
`updating` deja un historial de un cambio que un interbloqueo o una restricción pueden
impedir después.

**Nunca escribas el historial desde el controlador.** Si el registro depende de que
quien programa se acuerde de llamarlo, tarde o temprano habrá un camino que no lo
llame — un comando de consola, un seeder, una acción masiva — y el historial quedará
con huecos silenciosos.

## Precarga de prioridad

Al crear una tarea de tipo `correccion` o `soporte`, la prioridad se **precarga** en
`alta` (`TipoTarea::prioridadPorDefecto()`). Es un valor por defecto, **no una
restricción**: sigue siendo editable y la validación acepta cualquier prioridad.

## Al validar una transición

1. Comprueba el par con `EstadoTarea::puedeTransicionarA()`. No reimplementes la matriz.
2. Si el destino es `en_espera`, exige `motivo_espera`.
3. Si el destino es `cancelada`, exige `motivo_cancelacion`.
4. Rechaza con 403/422 — nunca ignores la transición en silencio.

Toda transición prohibida necesita una prueba explícita que compruebe que se rechaza.
