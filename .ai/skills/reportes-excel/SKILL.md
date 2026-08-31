---
name: reportes-excel
description: Usar al generar, modificar o probar cualquier archivo Excel; al escribir clases de export de maatwebsite/excel; al dar formato a hojas, celdas, bordes o rellenos de un xlsx; al construir la ruta de descarga de un reporte; al escribir el registro de trazabilidad de reportes emitidos; y al decidir qué columnas ve el cliente y cuáles son internas. Cubre los dos reportes en archivos separados, el formato de cuadrícula obligatorio, la convención de nombres y por qué la generación es síncrona.
---

# Reportes Excel

## Dos reportes, dos archivos. Nunca uno solo

| Reporte | Uso | Contenido |
|---|---|---|
| **Completo** | Interno | Todo lo trabajado en el periodo **incluidas las canceladas**, más los datos de **pago** (USD, TRM, COP) |
| **De cliente** | Se le entrega al cliente | Solo: tarea, estado, descripción, módulo |

> **Nunca los combines en un mismo archivo, ni siquiera en hojas separadas.** Un
> archivo que contiene los pagos acaba enviándose al cliente por error: basta un
> reenvío. La separación física es la única garantía real.

El **reporte completo incluye todo** lo trabajado en el periodo, canceladas incluidas:
el periodo se facturó por el trabajo hecho, y el trabajo descartado también consumió
tiempo. Omitirlo hace que el reporte no cuadre con la realidad.

## Estructura de hojas

**Reporte completo:**

| Hoja | Contenido |
|---|---|
| `Resumen` | Proyecto, periodo, fechas, conteo por estado, pago (USD, TRM, COP) |
| `Tareas` | Código, tarea, módulo, tipo, estado, prioridad, descripción |

**Reporte de cliente:**

| Hoja | Contenido |
|---|---|
| `Tareas` | Código, tarea, estado, descripción, módulo |

El reporte de cliente **no lleva hoja de resumen**: el resumen contiene el pago.

## Formato obligatorio

Estas cuatro cosas no son decorativas — sin ellas el archivo llega ilegible:

1. **Cuadrícula negra en todas las celdas.** Bordes finos negros en todo el rango con
   datos, encabezados incluidos. Sin cuadrícula, al imprimir se pierden las filas.
2. **Relleno sólido pálido por estado, con texto negro.** El color de estado se
   traslada al Excel como relleno sólido en tono pálido. **Texto siempre negro**: en
   Excel no hay modo oscuro, y un relleno pálido con texto claro es ilegible.
3. **`autoSize` en todas las columnas.**
4. **`wrapText` en la columna de descripción**, para que no se corte. Es la única
   columna con texto largo; sin `wrapText`, `autoSize` la estira a un ancho absurdo o
   la trunca.

## La generación es síncrona, con descarga directa

**No se encola.** El usuario pulsa y el archivo baja en la misma petición.

Razón: son reportes de un periodo — decenas o pocos cientos de filas, no millones.
Encolarlos añadiría notificación, almacenamiento temporal, limpieza y un estado
"generando" que el usuario tiene que vigilar, a cambio de ahorrar menos de un segundo.

El límite de `max_execution_time` (300 s en `docker/php/php.ini`) es holgado para este
volumen. Si algún día un periodo tarda de verdad, **eso** es el momento de encolar, no
antes.

## Trazabilidad: cada generación deja registro

Toda generación escribe una fila de registro permanente con:

| Campo | Contenido |
|---|---|
| Nombre de archivo | El nombre generado |
| Ruta | Dónde quedó almacenado |
| Filtros aplicados | Qué se incluyó y qué se excluyó |
| Conteo por estado | Cuántas tareas de cada estado entraron |
| Hash del archivo | Para comprobar que un archivo recibido es el que se emitió |

Sirve para responder "¿qué le mandamos al cliente en marzo y qué decía?" sin depender
de que alguien guardara el correo. El **hash** es lo que convierte esa respuesta en
prueba: permite verificar que el archivo que alguien tiene es exactamente el emitido.

El registro es **permanente**: no se borra al regenerar. Regenerar crea una fila nueva.

## Convención de nombres

```
{PREFIJO}_P{consecutivo}_{fecha}.xlsx
```

- `PREFIJO` — el prefijo del proyecto, el mismo de los códigos de tarea (`MAQ`).
- `P{consecutivo}` — número de periodo, `P1`, `P2`, `P12`.
- `fecha` — fecha de emisión, `AAAA-MM-DD`.

Ejemplo: `MAQ_P7_2026-03-14.xlsx`.

Los dos reportes del mismo periodo se distinguen por su ruta o sufijo de tipo, nunca
mezclando su contenido.

## Pruebas

Cada Excel necesita una prueba que **valide la estructura del archivo**, no solo que la
respuesta sea 200. Como mínimo:

- Las hojas esperadas existen, con esos nombres.
- La fila de encabezados tiene las columnas esperadas, en orden.
- **El reporte de cliente NO contiene columnas de pago.** Esta es la prueba más
  importante del módulo: es la que impide una filtración.
- El reporte completo incluye las tareas canceladas.

Una prueba que solo comprueba el código de estado pasaría con un archivo vacío.
