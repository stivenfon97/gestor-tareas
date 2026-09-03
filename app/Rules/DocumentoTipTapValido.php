<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * `contenido_json` es la fuente de verdad de una reunión (Paso 1). Esta regla
 * recorre el documento y rechaza cualquier nodo o marca fuera de la lista
 * blanca de `EditorEnriquecido.vue` — **sin `image`**, a propósito: es lo que
 * cierra, por el lado del JSON, la vía de un cliente hostil que arme a mano
 * un nodo de imagen con `src` en base64 sin pasar por el editor ni por el
 * panel de adjuntos.
 *
 * `SanearHtmlDeReunion` cierra la misma vía por el lado del HTML, que es lo
 * que de verdad se renderiza con `v-html`. Las dos capas son necesarias: esta
 * regla no sanea nada, solo rechaza; el HTML es lo único que se persiste
 * "confiado".
 */
final class DocumentoTipTapValido implements ValidationRule
{
    /** @var array<int, string> */
    private const NODOS_PERMITIDOS = [
        'doc', 'paragraph', 'text', 'heading', 'bulletList', 'orderedList',
        'listItem', 'blockquote', 'codeBlock', 'hardBreak', 'horizontalRule',
        'table', 'tableRow', 'tableHeader', 'tableCell',
    ];

    /** @var array<int, string> */
    private const MARCAS_PERMITIDAS = ['bold', 'italic', 'strike', 'highlight', 'link', 'code'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('validation.array')->translate();

            return;
        }

        if (($value['type'] ?? null) !== 'doc') {
            $fail(__('reuniones.validacion.documento_invalido'));

            return;
        }

        $error = $this->primerNodoInvalido($value);

        if ($error !== null) {
            $fail(__('reuniones.validacion.documento_invalido'));
        }
    }

    /**
     * @param  array<string, mixed>  $nodo
     */
    private function primerNodoInvalido(array $nodo): ?string
    {
        $tipo = $nodo['type'] ?? null;

        if (! is_string($tipo) || ! in_array($tipo, self::NODOS_PERMITIDOS, true)) {
            return $tipo ?? 'desconocido';
        }

        foreach ((array) ($nodo['marks'] ?? []) as $marca) {
            $tipoMarca = is_array($marca) ? ($marca['type'] ?? null) : null;

            if (! is_string($tipoMarca) || ! in_array($tipoMarca, self::MARCAS_PERMITIDAS, true)) {
                return $tipoMarca ?? 'desconocida';
            }
        }

        foreach ((array) ($nodo['content'] ?? []) as $hijo) {
            if (! is_array($hijo)) {
                return 'desconocido';
            }

            $error = $this->primerNodoInvalido($hijo);

            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }
}
