<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Alta de una cuenta del panel con la misma lógica que la app DuaLab (StoreAdminUserRequest):
 * nombre, email, rol y contraseña inicial. Además, opcionalmente, correo de acceso.
 */
class StoreUsuarioRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:120'],
            // Incluye cuentas en la papelera y las antiguas de la web (alumno, empresa…)
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'role'  => ['required', Rule::in(config('cms.roles_panel'))],
            'password'              => ['required', 'string', 'max:128', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['required', 'string'],
            'enviar_correo'         => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya hay una cuenta con este email (puede estar en la papelera de usuarios).',
            'role.in'      => 'Ese rol no existe en el panel.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
