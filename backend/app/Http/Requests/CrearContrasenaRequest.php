<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Crear la contraseña (invitación) o elegir una nueva (enlace de «la he olvidado»). */
class CrearContrasenaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token'    => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:190'],
            'modo'     => ['required', Rule::in(['invitacion', 'restablecer'])],
            'password' => ['required', 'string', 'max:128', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['password.confirmed' => 'Las contraseñas no coinciden.'];
    }
}
