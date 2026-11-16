<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarUsuariosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'buscar'   => ['nullable', 'string', 'max:100'],
            'rol'      => ['nullable', Rule::in(config('cms.roles_panel'))],
            'papelera' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
