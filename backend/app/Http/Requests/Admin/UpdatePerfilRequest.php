<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/** Mismo perfil que la app DuaLab (microretos: UpdatePerfilRequest), pidiendo además la contraseña actual. */
class UpdatePerfilRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'password'              => ['nullable', 'string', 'max:128', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['nullable', 'string'],
            // Solo si se cambia la contraseña: evita que una sesión olvidada abierta la cambie
            'current_password'      => ['required_with:password', 'nullable', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed'             => 'Las contraseñas no coinciden.',
            'current_password.required_with' => 'Escribe tu contraseña actual para cambiarla.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
        ];
    }
}
