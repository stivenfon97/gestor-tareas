/**
 * Slug a partir de un texto, equivalente al `Str::slug()` del servidor.
 *
 * Solo sirve para **previsualizar** el identificador mientras se escribe el
 * nombre. Quien decide el valor definitivo —y quien resuelve las colisiones— es
 * `ProyectoObserver@creating`: si el campo llega vacío, el servidor lo genera.
 */
export function aSlug(texto: string): string {
    return texto
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}
