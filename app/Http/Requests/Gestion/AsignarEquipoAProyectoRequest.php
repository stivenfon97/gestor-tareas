<?php

declare(strict_types=1);

namespace App\Http\Requests\Gestion;

use App\Enums\RolProyecto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarEquipoAProyectoRequest extends FormRequest
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
        return [
            'equipo_id' => ['required', 'integer', Rule::exists('equipos', 'id')],
            'rol' => ['required', Rule::enum(RolProyecto::class)],
        ];
    }
}
