<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIdiomaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'codigo' => ['required', Rule::in(array_keys(config('cms.idiomas_disponibles'))), 'unique:idiomas,codigo'],
        ];
    }

    public function messages(): array
    {
        return ['codigo.unique' => 'Ese idioma ya está añadido.'];
    }
}
