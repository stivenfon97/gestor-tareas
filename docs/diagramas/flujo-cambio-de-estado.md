# Flujo: cambio de estado de una tarea

Desde el clic hasta el registro en el historial. Escrito en la **fase 06**.

Lo que este diagrama existe para dejar claro es **dónde valida cada pieza y dónde
escribe**, porque a primera vista parece que el servicio y el observer hacen lo mismo.

```mermaid
sequenceDiagram
    autonumber
    actor Usuario
    participant Vue as ControlDeEstado.vue
    participant Ruta as PUT tareas/·/estado
    participant Req as CambiarEstadoTareaRequest
    participant Ctrl as EstadoTareaController
    participant Serv as CambiarEstadoTarea
    participant Obs as TareaObserver
    participant BD as Base de datos

    Usuario->>Vue: Pulsa "Mover a En espera"
    Note over Vue: Los botones son las transiciones<br/>que el servidor envió.<br/>La matriz no vive aquí.
    Vue->>Usuario: Pide el motivo (el destino lo exige)
    Usuario->>Vue: Escribe el motivo
    Vue->>Ruta: estado + motivo_espera

    Ruta->>Req: Valida forma
    Note over Req: Exige el motivo que el<br/>DESTINO pide, no el que<br/>la tarea tenga guardado.
    Req-->>Vue: 422 si falta el motivo

    Ruta->>Ctrl: Request válida
    Ctrl->>Ctrl: Gate::authorize('cambiarEstado')
    Ctrl-->>Vue: 403 si el rol no permite mover

    Ctrl->>Serv: (tarea, destino, motivos)

    Serv->>Serv: ¿Mismo estado?
    Note over Serv: Un estado hacia sí mismo<br/>no es transición: no se registra.

    Serv->>Serv: EstadoTarea::puedeTransicionarA()
    Serv-->>Ctrl: TransicionNoPermitida
    Ctrl-->>Vue: 422 con el mensaje traducido

    Serv->>Serv: Rellena fecha_inicio / fecha_fin
    Note over Serv: inicio solo la primera vez,<br/>fin también al reentrar.

    Serv->>BD: BEGIN
    Serv->>Obs: save() dispara updating
    Obs->>Obs: Revalida la matriz y exige el motivo
    Note over Obs: Última red: cubre al seeder,<br/>al comando de consola y a<br/>cualquier vía que no pase<br/>por el servicio.
    Obs->>Obs: Limpia el motivo del estado que se abandona
    Obs-->>Serv: TransicionNoPermitida aborta el guardado

    Obs->>BD: UPDATE tareas
    BD-->>Obs: ok
    Obs->>Obs: updated
    Obs->>BD: INSERT historial_estados
    Note over Obs,BD: Se escribe en `updated`, no en<br/>`updating`: el UPDATE puede fallar<br/>después y quedaría registrado<br/>un cambio que nunca ocurrió.
    Serv->>BD: COMMIT

    Serv-->>Ctrl: estado nuevo + transiciones disponibles
    Ctrl-->>Vue: redirect back + toast
    Vue->>Usuario: Badge nuevo y botones nuevos
```

## Quién hace qué

| Pieza | Valida | Escribe |
|---|---|---|
| `ControlDeEstado.vue` | Nada. Solo ofrece lo que recibió | Nada |
| `CambiarEstadoTareaRequest` | Forma: enum válido y motivo del destino | Nada |
| `EstadoTareaController` | Autorización (`cambiarEstado`) | Nada |
| `CambiarEstadoTarea` | La matriz, antes de tocar la base | `estado`, `fecha_inicio`, `fecha_fin`, motivos |
| `TareaObserver@updating` | La matriz otra vez, y el motivo | Limpia el motivo abandonado |
| `TareaObserver@updated` | Nada | `historial_estados` |

## Por qué el servicio y el observer validan lo mismo

No es duplicación, es **profundidad**:

- El **servicio** valida para que el usuario reciba un 422 legible sin que se abra
  ninguna transacción ni se toque una fila.
- El **observer** valida para que un seeder, un comando de consola o una acción masiva
  —que no pasan por el servicio— tampoco puedan escribir una transición prohibida.

Si solo estuviera el servicio, el día que alguien escriba
`$tarea->update(['estado' => 'entregada'])` en un comando, la matriz dejaría de existir en
silencio. Si solo estuviera el observer, el usuario recibiría un 500 en lugar de un
mensaje, y las fechas automáticas no se rellenarían.

## Por qué el historial no lo escribe el servicio

Porque entonces dependería de que alguien llame al servicio. El historial es el registro
que responde "¿quién movió esto y por qué?" meses después, y un hueco silencioso en él no
se detecta hasta que hace falta. En `updated` se escribe **siempre que el UPDATE haya
ocurrido de verdad**, venga de donde venga.
