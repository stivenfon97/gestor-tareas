<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Niveles afectados por un arrastre.
 *
 * Todos los ids —los que se mueven y el padre de destino— tienen que pertenecer
 * al proyecto de la ruta. Sin esa comprobación, un id ajeno enviado a mano
 * arrastraría un módulo de otro cliente al árbol propio.
 */
class ReordenarModulosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $delProyecto = Rule::exists('modulos', 'id')->where('proyecto_id', $this->proyecto()->getKey());

        return [
            'niveles' => ['required', 'array', 'min:1'],
            'niveles.*.parent_id' => ['nullable', 'integer', $delProyecto],
            'niveles.*.modulos' => ['present', 'array'],
            'niveles.*.modulos.*' => ['integer', 'distinct', $delProyecto],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'niveles.*.parent_id.exists' => __('modulos.validacion.padre_de_otro_proyecto'),
            'niveles.*.modulos.*.exists' => __('modulos.validacion.modulo_de_otro_proyecto'),
        ];
    }

    /**
     * @return array<int, array{parent_id: int|null, modulos: array<int, int>}>
     */
    public function niveles(): array
    {
        return array_map(static fn (array $nivel): array => [
            'parent_id' => isset($nivel['parent_id']) ? (int) $nivel['parent_id'] : null,
            'modulos' => array_map(intval(...), array_values($nivel['modulos'])),
        ], $this->validated('niveles'));
    }

    private function proyecto(): Proyecto
    {
        /** @var Proyecto */
        return $this->route('proyecto');
    }
}
