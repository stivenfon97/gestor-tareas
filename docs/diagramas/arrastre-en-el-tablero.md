# Arrastrar una tarjeta en el tablero

Desde que se suelta la tarjeta hasta que queda fila en el historial, **incluido el camino
de vuelta**. Es el recorrido del tablero agrupado **por estado**, que es el único en el
que soltar una tarjeta cambia el estado de una tarea.

Agrupado por módulo o por prioridad el recorrido es otro y mucho más corto: un `update`
de un campo por `MoverTareaEnTableroController`, sin matriz, sin motivo y sin historial.
Agrupado por tipo no hay recorrido: el tipo se fija al crear y no cambia.

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant S as SortableJS
    participant T as useTablero (Vue)
    participant C as EstadoTareaController
    participant V as CambiarEstadoTarea
    participant O as TareaObserver
    participant BD as Base de datos

    U->>S: suelta la tarjeta sobre una columna
    S->>T: onMove(columna candidata)
    Note over T: `puedeIr()` mira las transiciones<br/>que mandó el servidor con la tarjeta.<br/>La matriz no se replica en Vue.

    alt La columna no admite la tarjeta
        T-->>S: false
        Note over S: La columna aparece no disponible<br/>y el soltar se rechaza.
        S->>T: change(added) en la columna del camino
        Note over T: La intención era otra columna:<br/>se restaura el modelo y se sube<br/>`version` para que Vue reconstruya<br/>las listas que SortableJS ya movió.
        T-->>U: la tarjeta vuelve a su columna
    else La columna la admite
        T-->>S: true
        S->>T: change(added)
        T->>T: mueve la tarjeta en pantalla (optimista)

        alt El destino exige motivo (en_espera, cancelada)
            T-->>U: diálogo de motivo
            alt El usuario cancela
                T->>T: restaura la instantánea previa
                T-->>U: la tarjeta vuelve a su columna
            else El usuario escribe el motivo
                T->>C: PUT tareas/{t}/estado
            end
        else El destino no exige motivo
            T->>C: PUT tareas/{t}/estado
        end

        C->>V: __invoke(tarea, destino, motivos)
        V->>V: valida contra la matriz
        alt Transición no permitida
            V-->>C: TransicionNoPermitida
            C-->>T: 422
            T->>T: revierte a la instantánea previa
            T-->>U: la tarjeta vuelve y se avisa del error
        else Transición permitida
            V->>V: rellena fecha_inicio / fecha_fin
            V->>BD: save()
            BD-->>O: updating
            O->>O: revalida y exige el motivo
            BD-->>O: updated
            O->>BD: escribe historial_estados
            C-->>T: redirección con el toast
            T->>T: persiste el orden de la columna destino
        end
    end

    Note over T: Si la respuesta no llega, llega rota<br/>o es un 500, `onFinish` revierte igual:<br/>se revierte salvo que se confirme el éxito.
```

## Las tres cosas que este diagrama existe para dejar claras

1. **La matriz solo vive en el servidor.** La interfaz pregunta con
   `puedeIr()`, y lo que responde son las transiciones que el servidor mandó con cada
   tarjeta. Replicarla en Vue sería una cuarta fuente de verdad —enum, skill, diagrama y
   cliente— y la única que ninguna prueba comprueba.

2. **Hay tres caminos de vuelta, no uno.** El soltar rechazado, el diálogo cancelado y el
   error del servidor son tres momentos distintos en los que la tarjeta tiene que volver a
   su sitio. Una tarjeta que se queda donde el usuario la soltó mientras el servidor dice
   que no es peor que no tener arrastre, porque miente sobre lo que hay guardado.

3. **El historial no lo escribe el servicio, lo escribe el observer.** Si dependiera de
   que alguien llame al servicio, algún día habría un camino que no lo llama —una acción
   masiva, un comando— y quedaría un hueco silencioso.
