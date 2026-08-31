<?php

declare(strict_types=1);

namespace App\Http\Requests\Gestion;

use App\Enums\RolProyecto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarUsuarioAProyectoRequest extends FormRequest
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
            'usuario_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'rol' => ['required', Rule::enum(RolProyecto::class)],
        ];
    }
}
