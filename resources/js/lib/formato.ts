import { currentLocale } from 'laravel-vue-i18n';

/**
 * Fecha y monto según el idioma activo.
 *
 * `laravel-vue-i18n` resuelve el texto de la interfaz, pero una fecha o un
 * monto no son texto de diccionario: son números y calendarios que `Intl`
 * sabe formatear por locale. Sin esto, la interfaz cambiaba de idioma y
 * seguía enseñando `2026-09-03` y `3184.00` sin separador de miles en las
 * dos —el servidor manda la fecha en ISO y el monto en el `string` crudo del
 * cast `decimal`—, que es información igual de correcta y bastante más difícil
 * de leer que en cualquiera de los dos idiomas.
 *
 * El locale de `Intl` no es el mismo código que el de `laravel-vue-i18n`:
 * este proyecto solo tiene dos (`es`, `en`), pero `Intl` necesita saber
 * *qué* español y *qué* inglés — el dominio es colombiano (`APP_TIMEZONE`,
 * el glosario, los montos en COP), así que `es` mapea a `es-CO`.
 */
const MAPA_LOCALE_INTL: Record<string, string> = {
    es: 'es-CO',
    en: 'en-US',
};

const localeIntl = (): string =>
    MAPA_LOCALE_INTL[currentLocale.value] ?? 'es-CO';

/**
 * Formatea una fecha en formato ISO (`toDateString()`, `YYYY-MM-DD`) según el
 * idioma activo.
 *
 * `new Date('2026-09-03')` la interpreta en UTC medianoche: en cualquier zona
 * horaria al oeste de Greenwich (incluida `America/Bogota`) el navegador la
 * muestra un día antes. Se parsean los tres componentes a mano y se
 * construye la fecha en hora local, sin ese salto.
 */
export function formatearFecha(
    fechaIso: string | null | undefined,
    opciones: Intl.DateTimeFormatOptions = { dateStyle: 'medium' },
): string {
    if (!fechaIso) {
        return '';
    }

    const [anio, mes, dia] = fechaIso.split('-').map(Number);

    if (!anio || !mes || !dia) {
        return fechaIso;
    }

    return new Intl.DateTimeFormat(localeIntl(), opciones).format(
        new Date(anio, mes - 1, dia),
    );
}

const DECIMALES_POR_MONEDA = { USD: 2, COP: 2 } as const;

/**
 * Formatea un monto —el `string` que llega del cast `decimal` del modelo, o
 * un número— como moneda, con símbolo y separadores según el idioma activo.
 *
 * Siempre a dos decimales en las dos monedas: son montos de negocio, no
 * cifras redondeadas para mostrar. `Number(valor)` es solo para dar formato
 * de vista —el dinero como tal sigue viajando como `decimal` del servidor al
 * cliente y nunca se hace aritmética sobre este resultado.
 */
export function formatearMonto(
    valor: string | number,
    moneda: keyof typeof DECIMALES_POR_MONEDA,
): string {
    const numero = typeof valor === 'string' ? Number(valor) : valor;

    if (Number.isNaN(numero)) {
        return '';
    }

    return new Intl.NumberFormat(localeIntl(), {
        style: 'currency',
        currency: moneda,
        minimumFractionDigits: DECIMALES_POR_MONEDA[moneda],
        maximumFractionDigits: DECIMALES_POR_MONEDA[moneda],
    }).format(numero);
}

/** Un número plano (conteos, días) con el separador de miles del idioma activo. */
export function formatearNumero(
    valor: number,
    opciones?: Intl.NumberFormatOptions,
): string {
    return new Intl.NumberFormat(localeIntl(), opciones).format(valor);
}
