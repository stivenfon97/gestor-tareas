<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta y edición de clientes. La autorización la resuelve `ClientePolicy` desde
 * el controlador; aquí solo se valida.
 */
class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'contacto_nombre' => ['nullable', 'string', 'max:255'],
            'contacto_email' => ['nullable', 'email', 'max:255'],
            'contacto_telefono' => ['nullable', 'string', 'max:50'],
            'notas' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
