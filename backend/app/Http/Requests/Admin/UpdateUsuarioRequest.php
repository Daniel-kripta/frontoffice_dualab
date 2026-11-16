<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Nombre, email, rol y, opcionalmente, contraseña nueva (como UpdateAdminUserRequest de la app DuaLab). */
class UpdateUsuarioRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:120'],
            'email'    => ['sometimes', 'string', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($this->route('usuario'))],
            'role'     => ['sometimes', Rule::in(config('cms.roles_panel'))],
            'password' => ['nullable', 'string', 'max:128', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'Ya hay una cuenta con este email (puede estar en la papelera de usuarios).',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
