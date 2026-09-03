# Flujo de generación de reporte

Recorrido de generar los dos reportes Excel (completo y de cliente), con el registro de
trazabilidad que deja cada emisión y las dos ramas de descarga -actual e histórica- que
convergen en el mismo código.

Detalle en [`../modulos/reportes.md`](../modulos/reportes.md).

```mermaid
flowchart TD
    subgraph generar ["Generar (una sola vez por clic)"]
        eleccion["Elegir periodo y tipo en reportes/Index.vue"]
        preview["Vista previa del conteo -llega con la página, sin petición-"]
        post["POST proyectos/{proyecto}/reportes\nReporteController@store"]
        gate1{"Gate::authorize('create')"}
        accion["GenerarReporte\n(App\\Actions\\GenerarReporte)"]
        construir["Construye el snapshot desnormalizado\n-texto, no ids- según el tipo"]
        fila["Reporte::create()\nfilas + resumen_estados + hash"]
        redirect["Redirige a proyectos.reportes.index\n(historial ya actualizado)"]
        flash["Flash reporte_generado_id"]

        eleccion --> preview --> post --> gate1
        gate1 -->|"no"| f403a["403"]
        gate1 -->|"sí"| accion --> construir --> fila --> redirect --> flash
    end

    subgraph descargar ["Descargar (mismo camino para las dos ramas)"]
        get["GET reportes/{reporte}/descargar\nReporteController@descargar"]
        gate2{"Gate::authorize('view')"}
        leer["Lee reporte.snapshot\n-nunca vuelve a consultar tareas, notas o pagos-"]
        export{"tipo"}
        completo["ReporteCompletoExport\n(WithMultipleSheets: Resumen, Tareas, Notas, Pagos)"]
        cliente["HojaClienteExport\n(una hoja: Tarea, Estado, Descripción, Módulo)"]
        excel["Excel::download()\ntemporal en storage/framework/cache, borrado tras enviarlo"]

        get --> gate2
        gate2 -->|"no"| f403b["403"]
        gate2 -->|"sí"| leer --> export
        export -->|"completo"| completo --> excel
        export -->|"cliente"| cliente --> excel
    end

    flash -->|"la interfaz navega a la URL de descarga con el id recién creado\n= rama «actual»"| get
    historial(["Fila del historial, de cualquier fecha\nbotón «Descargar histórico»"]) -->|"= rama «histórica»"| get
```

Las dos ramas de descarga —el primer clic tras generar y un registro histórico
cualquiera— entran por el mismo `ReporteController@descargar`, que **siempre** lee del
`snapshot` y nunca de una consulta en vivo. No hay una generación de archivo distinta
para cada una: «actual» es, literalmente, leer el snapshot que se acaba de escribir.
