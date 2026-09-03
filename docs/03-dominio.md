# Dominio

Las reglas que gobiernan el sistema, numeradas para poder citarlas desde el código y
desde otros documentos. Las que todavía no tienen implementación se marcan con la fase
que las va a construir.

## Glosario

Ver la tabla de glosario en [`../.ai/guidelines/proyecto.md`](../.ai/guidelines/proyecto.md).

## Ciclo de vida de una tarea

Ver [`diagramas/ciclo-de-vida-tarea.md`](diagramas/ciclo-de-vida-tarea.md).

---

## 1. Clasificación de tareas

**D1.1 — Estado, tipo y prioridad son tres ejes independientes.** Una tarea tiene los
tres a la vez y ninguno condiciona a los otros.

**D1.2 — "Corrección" y "Soporte" son tipos, no estados.** Recorren el mismo ciclo de
vida que cualquier otra tarea. Convertirlos en estados es el error más frecuente en
este dominio y rompe la matriz de transiciones.

**D1.3 — El tipo no cambia.** Se fija al crear la tarea. Si el trabajo resultó ser otra
cosa, se crea otra tarea.

**D1.4 — La prioridad se precarga según el tipo.** `correccion` y `soporte` nacen en
`alta`; el resto en `media`. Es un valor por defecto, **no una restricción**: sigue
siendo editable y la validación acepta cualquier prioridad.
*Implementado en `TipoTarea::prioridadPorDefecto()` y `TareaObserver::creating()`.*

**D1.5 — No se registran horas en ninguna parte.** Ni por tarea, ni por periodo. No hay
`horas_estimadas`, `horas_trabajadas`, cronómetros ni timesheets. Si un requisito parece
necesitarlas, la respuesta es que no las necesita.

## 2. Transiciones de estado

**D2.1 — Solo son válidas las once transiciones de la matriz.** La fuente de verdad
ejecutable es `EstadoTarea::transicionesPermitidas()`.
`tests/Unit/MatrizDeTransicionesTest.php` comprueba en cada corrida que la matriz de la
skill, la del diagrama y el enum dicen exactamente lo mismo.

**D2.2 — `en_espera` significa exclusivamente falta de información o de recursos** para
entender o ejecutar la tarea. No es un cajón de sastre para cualquier demora.

**D2.3 — No existe `terminada → en_espera`.** Cuando una tarea llega a `terminada` el
commit ya está en la nube, y bajarlo al servidor es trámite, no bloqueo. Añadir esta
transición convertiría `en_espera` en un estado que no significa nada.

**D2.4 — `en_espera` se alcanza también desde `pendiente`.** Una tarea puede estar
bloqueada sin haberse empezado: el cliente nunca explicó qué quería.

**D2.5 — `en_espera → pendiente` existe.** Llegó la información y la tarea vuelve a la
cola sin haberse empezado.

**D2.6 — `entregada` es terminal.** Un fallo posterior no reabre la tarea: se crea una
tarea nueva de tipo `correccion`. Así se preserva qué se entregó y cuándo, y las
correcciones cuentan como trabajo real en el periodo en que ocurren, que es lo que hace
que el reporte del periodo sea honesto.

**D2.7 — `cancelada` es terminal.** Cancelar por error obliga a crear otra tarea. Es
deliberado: el estado registra una decisión del cliente, no un borrador.

**D2.8 — `terminada` no se cancela.** Lo que ya está hecho se entrega, o se abre una
tarea nueva que lo revierta. Borrar el rastro del trabajo hecho falsea el periodo.

**D2.9 — Pasar a `en_espera` exige `motivo_espera`; pasar a `cancelada` exige
`motivo_cancelacion`.** Son **campos distintos** y no se reutilizan entre sí: mezclarlos
hace ilegible el historial. Ambos se limpian al salir del estado, pero el texto queda en
el historial.

**D2.10 — La validación vive en el Observer, no en el controlador.** Así ninguna vía de
escritura —comando de consola, acción masiva, seeder— puede saltársela.
*Implementado en `TareaObserver::updating()`, que lanza `TransicionNoPermitida`.*

**D2.11 — El historial de estados lo escribe el Observer.** Se escribe en `updated`,
cuando el UPDATE ya se ejecutó: hacerlo antes dejaría registrado un cambio que un
interbloqueo o una restricción pueden impedir después.

**D2.12 — Toda transición pasa por un único servicio.** `CambiarEstadoTarea` es la
puerta: valida antes de tocar la base, rellena las fechas automáticas y devuelve las
transiciones disponibles desde el estado nuevo. Ninguna vía de escritura toca `estado`
directamente sobre el modelo; el Observer sigue siendo la última red para lo que no pase
por aquí.
*Implementado en `App\Services\CambiarEstadoTarea` (fase 06).*

**D2.13 — `fecha_inicio` se fija en la primera entrada a `en_proceso` y no se
sobrescribe.** Marca cuándo se empezó de verdad: volver de `en_espera` o de una revisión
fallida no es empezar de nuevo, y sobrescribirla borraría cuánto llevaba abierta la
tarea.

**D2.14 — `fecha_fin` sí se actualiza al reentrar en `terminada`.** Marca cuándo quedó
lista. Si la revisión falló y hubo que retocar, la fecha buena es la del segundo intento:
es la que el cliente ve en el reporte del periodo.

**D2.15 — El servidor dice qué transiciones están disponibles.** La interfaz no deduce la
matriz: la recibe con cada tarea, junto con el campo de motivo que cada destino exige.
Replicarla en el cliente crearía una cuarta fuente de verdad —enum, skill, diagrama y
Vue— y sería la que nadie comprueba.

## 3. Identificación de tareas

**D3.1 — El código es correlativo por proyecto y continuo de por vida.** `POR-0001`,
`POR-0002`… No se reinicia por periodo.

**D3.2 — El número sale de un contador con bloqueo, no de `max(codigo) + 1`.** Dos
creaciones simultáneas leerían el mismo máximo y devolverían el mismo número; y un
borrado forzado haría que un código se reutilizara. El `unique` de `codigo` es la última
red de seguridad, no la primera.
*Implementado con `contadores_tarea` y `lockForUpdate()` en `TareaObserver`.*

**D3.3 — Una corrección se vincula con la entrega que la originó.** La columna
`tareas.tarea_origen_id` (`nullable`, `set null`) es el precio de haber hecho `entregada`
terminal (D2.6): sin ella, "cuántas correcciones generó esta entrega" solo se respondía
leyendo descripciones.

El vínculo tiene cuatro invariantes, y las cuatro se comprueban en `TareaObserver` además
de en el formulario:

1. Solo lo tienen las tareas de tipo `correccion` y `soporte`.
2. El origen pertenece **al mismo proyecto**.
3. Una tarea no es su propio origen.
4. La cadena de orígenes no se cierra sobre sí misma.

Es `set null` y no `cascade` a propósito: si se borra la entrega original, la corrección
**sigue siendo trabajo hecho** que cuenta en su periodo. Perderla falsearía ese reporte.
*Implementado en la fase 06; excepción `VinculoDeOrigenInvalido`.*

## 4. Proyectos, periodos y pagos

**D4.1 — Los periodos son por proyecto**, no por cliente.

**D4.2 — Un periodo tiene exactamente un pago.** Relación uno a uno, garantizada por el
`unique` en `pagos.periodo_id`.

**D4.3 — El cierre de un periodo es manual.** El sistema nunca cierra un periodo solo:
no hay job programado ni observer que lo haga al llegar la fecha de fin.

**D4.4 — Sobrepasar la fecha de fin no es un error.** Un periodo abierto más allá de su
fecha prevista es un estado válido y frecuente. La interfaz lo señala; la validación no
lo rechaza. *Expuesto en `Periodo::estaVencido()`.*

**D4.5 — La TRM se consulta a la fecha del pago**, no a la de hoy, y el valor queda
siempre editable a mano. `origen_tasa` registra si vino de la API o se escribió.

**D4.6 — `monto_cop` se persiste calculado, no se deriva al leer.** Corregir mañana la
tasa de un pago viejo no debe reescribir lo que ya se cobró. El cálculo va por `bcmath`
sobre cadenas: en coma flotante se pierden centavos y el reporte deja de cuadrar.
*Implementado en `PagoObserver::saving()`.*

**D4.7 — Una tarea en un estado no terminal puede arrastrarse al siguiente periodo**
conservando `periodo_origen_id`. Eso permite distinguir el trabajo que nació en el
periodo del que venía heredado. "No terminal" es D2.6/D2.7 (solo `entregada` y
`cancelada` lo son), no "distinta de `terminada`": ver D4.13, que lo confirma sin
ambigüedad porque el nombre del estado se presta a confundirse con la propia palabra.

**D4.8 — Un proyecto admite un solo periodo `abierto` a la vez, garantizado por la base
de datos.** Columna generada `proyecto_si_abierto` (`VIRTUAL`: en MySQL 8.4 una `STORED`
que depende de una columna con clave foránea revienta el `ALTER`) más índice único, el
mismo mecanismo que la vista predeterminada de la fase 08. *Implementado en
`2026_09_02_120000_garantizar_un_periodo_abierto_por_proyecto`.*

**D4.9 — Un periodo `cerrado` no admite tareas nuevas ni traslados hacia él.** Una tarea
solo puede apuntar a un periodo `abierto`, salvo que sea el que ya tenía —editar el resto
de una tarea no puede fallar porque su periodo se cerró después—. *Implementado en
`TareaRequest::periodoAbiertoOSinCambios()`.*

**D4.10 — Trasladar una tarea entre periodos no es un cambio de estado.** La tarea
conserva el suyo, no pasa por `CambiarEstadoTarea` y no escribe en `historial_estados`.
`orden` tampoco se toca: sigue siendo del proyecto, no del periodo, porque cada vista que
lo usa ordena dentro de su propio filtro y un valor compartido entre periodos nunca
colisiona con el de otro grupo. *Implementado en `CerrarPeriodo`.*

**D4.11 — El cierre es una operación en lote, en una sola transacción.** Cerrar el
periodo, trasladar lo seleccionado y abrir el siguiente ocurren juntos o no ocurre nada:
un cierre a medias es el peor estado posible del sistema.

**D4.12 — Ninguna consulta a un servicio externo bloquea el registro de datos del
usuario.** La TRM se consulta con un tiempo de espera corto y un solo intento; si la API
no responde, el formulario de pago sigue funcionando con captura manual y `origen_tasa`
queda como `manual`. *Implementado en `ConsultarTrm`.*

**D4.13 — El traslado incluye la tarea en estado `terminada`.** *Confirmado en la fase
10, ampliando lo que decía el prompt original de la fase 09.* Una tarea `terminada`
pero sin entregar sigue siendo trabajo abierto: el commit está en la nube pero el
cliente todavía no lo confirmó, y esa entrega va a ocurrir en el periodo siguiente —
que es donde debe contar (D2.6). Solo se excluyen del traslado los dos estados
terminales, `entregada` y `cancelada` (D2.6, D2.7;
`EstadoTarea::esTerminal()`). *Implementado en `CerrarPeriodo`,
`CierrePeriodoController::create()` y `CierrePeriodoRequest`, los tres con
`whereNotIn('estado', [Entregada, Cancelada])`.*

## 5. Personas y permisos

**D5.1 — Un usuario tiene un solo rol por proyecto**, garantizado por el `unique` en
`(proyecto_id, usuario_id)`.

**D5.2 — El rol se resuelve siempre desde el pivote `proyecto_usuario`, nunca desde el
equipo.** El equipo es un atajo de asignación masiva, no una fuente de autoridad.

**D5.3 — Los cuatro roles de proyecto son `lider`, `colaborador`, `observador` y
`cliente`.**

**D5.4 — Un usuario que creó tareas, notas, reuniones o reportes no se puede borrar**
(`restrict`): esos registros dicen quién hizo qué. Un usuario que solo asistió a una
reunión sí, y el asistente conserva el nombre escrito.

## 6. Visibilidad

**D6.1 — Notas, reuniones y archivos nacen `interna`.** Exponer algo al cliente es
siempre una decisión explícita.

**D6.2 — La exclusión se aplica en la consulta, no ocultando columnas en la interfaz.**
Un dato que llega al navegador y se esconde con CSS es un dato filtrado.

**D6.3 — El cliente no ve pagos, reuniones internas ni archivos internos.**

## 7. Reportes

**D7.1 — Hay dos reportes en archivos separados**: el **completo** (uso interno,
incluye pagos) y el **de cliente** (solo tarea, estado, descripción y módulo). Nunca en
el mismo archivo. *Implementado en `App\Exports\ReporteCompletoExport` (cuatro hojas) y
`App\Exports\HojaClienteExport` (una hoja); nunca se instancian juntas.*

**D7.2 — Los `.xlsx` no se persisten en disco.** Se generan bajo demanda, síncronos, con
descarga directa. *`ReporteController::descargar()` llama a `Excel::download()`, que
escribe un temporal en `storage/framework/cache/laravel-excel` y lo borra al terminar de
enviarlo — nunca un disco de la aplicación.*

**D7.3 — La trazabilidad la da el `snapshot`**, que guarda las filas exactas exportadas,
desnormalizadas -texto, no ids-. Permite dos modos de descarga: **actual** (recalcula con
los datos de hoy) e **histórico** (reconstruye desde el snapshot, idéntico a lo que se
envió). Sin él, regenerar en noviembre el reporte de julio produciría un archivo
distinto y la pregunta "¿qué vio el cliente?" quedaría sin respuesta. *Implementado en
`App\Actions\GenerarReporte`.*

**D7.4 — El sufijo del nombre es obligatorio**:
`{PREFIJO}_P{consecutivo}_{fecha}_completo.xlsx` y
`{PREFIJO}_P{consecutivo}_{fecha}_cliente.xlsx`. Dos archivos con el mismo nombre en la
carpeta de descargas es justo el escenario que provoca enviarle los pagos al cliente.

**D7.5 — Una sola ruta de descarga sirve a la «actual» y a la «histórica».**
`ReporteController::store()` solo escribe la fila -es la parte auditable-;
`ReporteController::descargar()` solo lee del snapshot, nunca de la base de datos en
vivo. La descarga «actual» es, literalmente, pedir esa misma ruta con el id que se
acaba de crear: no hay una generación de archivo distinta para el primer clic. Evita dos
caminos de construcción del Excel que puedan divergir.

**D7.6 — El snapshot de tipo `cliente` no contiene resumen, notas ni pagos en absoluto**,
no solo esas hojas sin pintar. `GenerarReporte` construye una estructura distinta según
el tipo: la de cliente nunca tiene esas claves. Es la garantía real de que no hay
filtración posible -no hay dato que omitir en el Excel porque la fila nunca lo tuvo-, no
una columna que se decide no mostrar.

**D7.7 — La paleta de rellenos del Excel es propia, no la de la interfaz.** Los tokens de
`resources/css/app.css` están calibrados para pantalla y modo oscuro: los seis son
pasteles muy pálidos, con luminosidad casi idéntica entre sí, y en la escala de grises de
una impresora colapsan en el mismo gris casi blanco. `App\Exports\PaletaEstadoExcel`
fija en cambio un salto de luminosidad deliberado entre los seis estados conservando el
matiz de cada familia, para que sigan siendo seis grises distintos impresos en blanco y
negro.

## 8. Archivos

**D8.1 — Límites definitivos**: imágenes 5 MB, documentos 20 MB, respaldos `.sql`
256 MB. *Implementado en `App\Services\Adjuntos` (constantes `LIMITE_*`), leídas a la
vez por `acceptsFile()` de cada modelo y por `Adjuntos::reglasDeArchivo()`.*

**D8.2 — Los respaldos `.sql` van a disco privado**, jamás al público. Cuelgan
únicamente de `Proyecto` — `imagenes` y `documentos` cuelgan de `Tarea` y `Reunion`.

**D8.3 — Solo la colección `imagenes` lleva conversiones.** Un dump de 250 MB nunca
debe pasar por procesamiento de imagen.

**D8.4 — El tipo de un archivo se valida por su contenido real, nunca por su
extensión.** `mimetypes:` (lee con `finfo`), nunca `mimes:` (infiere por extensión); lo
mismo en `acceptsMimeTypes()` de Medialibrary. Un `.sql` que en realidad es un `.php`
subido a un disco público es ejecución remota. *Implementado en
`Adjuntos::reglasDeArchivo()` y en `registerMediaCollections()` de cada modelo.*

**D8.5 — Un adjunto de `Tarea` o `Proyecto` se trata siempre como interno**, porque
ninguno de los dos tiene una marca de visibilidad propia (a diferencia de `Reunion`,
que sí la tiene y donde el adjunto hereda la del dueño). Un `cliente` no ve ninguno, ni
en pantalla ni en el JSON de la página — la clave falta del todo en las props cuando
`ResolverRolProyecto::veLoInterno()` es falso (D6.2). *Implementado en
`MediaPolicy::visibilidadDe()` y en el gateo de `adjuntos`/`respaldos` en
`TareaController::show`/`ProyectoController::show`.*

**D8.6 — Ningún archivo se sirve por URL directa al disco.** Todo adjunto pasa por
`archivos.descargar` (`ArchivoController`), que evalúa `MediaPolicy` antes de emitir un
byte — incluida la conversión `miniatura`, servida por la misma ruta con
`?conversion=miniatura` en vez de abrir una segunda.
