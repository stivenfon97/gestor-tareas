---
paths:
  - 'app/Exports/**'
---

# Exports

## El snapshot guarda los valores tal como salieron al archivo, nunca identificadores
`App\Actions\GenerarReporte` construye la fila de `reportes.snapshot` con texto ya
resuelto -nombre de módulo, etiqueta de estado, nombre de tarea, descripción-, nunca con
`modulo_id`, el valor crudo del enum ni ningún otro id. Con `modulo_id`, un renombrado
posterior del módulo cambia lo que muestra el reporte histórico y deja de ser lo que se
le envió al cliente en su momento (D7.3, D7.6 en `docs/03-dominio.md`). Las clases
`Hoja*Export`/`ReporteCompletoExport`/`HojaClienteExport` solo leen ese snapshot ya
resuelto: no vuelven a consultar la base de datos ni construyen texto nuevo a partir de
ids.

## El reporte de cliente no contiene datos de pago en ninguna hoja ni celda
No es que la hoja «Pagos» no se pinte en `HojaClienteExport`: el snapshot de tipo
`cliente` que arma `GenerarReporte` **no tiene esa clave en absoluto** (D7.6). Es la
única garantía real contra una filtración -no hay dato que omitir al exportar porque la
fila nunca lo tuvo-, y la prueba que lo demuestra
(`tests/Feature/Reportes/ReporteClienteSinPagosTest.php`) recorre el `.xlsx` generado
celda por celda buscando montos, tasas y referencias, no el código que lo produce. Es la
única prueba que de verdad impide que un cambio futuro cuele un dato financiero por una
columna nueva.

## Fechas como fecha y montos como número, nunca como texto
El binder de valores de maatwebsite/excel formatea un `DateTimeInterface` a cadena
(`Y-m-d H:i:s`) antes de escribirlo, y esa cadena se guarda como texto, no como fecha.
Toda fecha se convierte primero con `FormatoDeHoja::fechaExcel()` -que usa
`PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel()`- y la columna lleva su `numFmt` vía
`WithColumnFormatting`. Los montos se castean a `float` explícito antes de escribirlos,
nunca se dejan como el `string` que devuelve el cast `decimal:N` del modelo. El
destinatario ordena y filtra en Excel; con texto, ordena alfabéticamente y nadie entiende
por qué "1500" queda después de "200".
