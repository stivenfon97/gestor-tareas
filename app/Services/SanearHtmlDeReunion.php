<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanea en el servidor el HTML que produce el editor de reuniones.
 *
 * El HTML que llega aquí es HTML que un usuario puede fabricar: llega de una
 * petición HTTP, no del editor. `contenido_html` se renderiza con `v-html`,
 * así que un fallo de saneado es ejecución de código en el navegador de quien
 * abra la reunión.
 *
 * Lista blanca, no lista negra: por defecto `HtmlSanitizerConfig` **descarta**
 * cualquier etiqueta no permitida, con sus hijos —incluido `<script>` y su
 * contenido de texto—, así que no hace falta enumerar qué se prohíbe.
 *
 * **Sin `<img>` a propósito.** El esquema del editor (`EditorEnriquecido.vue`)
 * tampoco registra un nodo de imagen: una imagen pegada no puede convertirse
 * en contenido embebido ni por accidente. Las imágenes van al panel de
 * adjuntos, como cualquier otro archivo (ver `DocumentoTipTapValido`, que
 * cierra la misma vía por el lado del JSON).
 */
final class SanearHtmlDeReunion
{
    public function __invoke(string $html): string
    {
        return $this->saneador()->sanitize($html);
    }

    private function saneador(): HtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('hr')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('s')
            ->allowElement('mark')
            ->allowElement('h1')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('blockquote')
            ->allowElement('pre')
            ->allowElement('code')
            ->allowElement('table')
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th')
            ->allowElement('td')
            ->allowElement('a', ['href'])
            // Ni "javascript:" ni "data:": solo esquemas de enlace de verdad.
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            // Forzado siempre, sin importar lo que traiga el HTML de entrada:
            // un enlace pegado no puede abrir la página en el mismo contexto.
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->forceAttribute('a', 'target', '_blank')
            ->withMaxInputLength(100_000);

        return new HtmlSanitizer($config);
    }
}
