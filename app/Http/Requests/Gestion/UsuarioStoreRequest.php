<?php

declare(strict_types=1);

namespace App\Http\Requests\Gestion;

use App\Concerns\PasswordValidationRules;
use App\Enums\RolGlobal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsuarioStoreRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * La autorización real la hace el middleware `can:usuarios.gestionar` de la
     * ruta; aquí solo se valida.
     */
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => $this->passwordRules(),
            'rol_global' => ['required', Rule::enum(RolGlobal::class)],
            'activo' => ['boolean'],
        ];
    }
}
