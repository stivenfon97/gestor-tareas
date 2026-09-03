<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TipoReunion;
use App\Enums\Visibilidad;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Rules\DocumentoTipTapValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de una reunión.
 *
 * `contenido_html` se valida como cadena aquí; el saneado con lista blanca lo
 * aplica `SanearHtmlDeReunion` en el controlador, **después** de validar —
 * nunca se confía en lo que llegó del navegador.
 */
class ReunionRequest extends FormRequest
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
        $proyecto = $this->proyecto();

        return [
            'fecha' => ['required', 'date'],
            'tipo' => ['required', Rule::enum(TipoReunion::class)],
            'titulo' => ['required', 'string', 'max:255'],
            'contenido_json' => ['required', 'array', new DocumentoTipTapValido],
            'contenido_html' => ['required', 'string', 'max:100000'],
            'acuerdos' => ['nullable', 'string', 'max:5000'],
            'visibilidad' => ['required', Rule::enum(Visibilidad::class)],
            'asistentes' => ['required', 'array', 'min:1'],
            'asistentes.*.nombre' => ['required', 'string', 'max:255'],
            // Solo alguien con fila en el pivote de este proyecto: elegir de
            // ese desplegable no puede terminar apuntando a un usuario ajeno.
            'asistentes.*.usuario_id' => [
                'nullable', 'integer',
                Rule::exists('proyecto_usuario', 'usuario_id')->where('proyecto_id', $proyecto->getKey()),
            ],
            'asistentes.*.organizacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * El proyecto sale de la ruta al crear y de la propia reunión al editar.
     */
    public function proyecto(): Proyecto
    {
        $proyecto = $this->route('proyecto');

        if ($proyecto instanceof Proyecto) {
            return $proyecto;
        }

        /** @var Reunion $reunion */
        $reunion = $this->route('reunion');

        return $reunion->proyecto;
    }
}
