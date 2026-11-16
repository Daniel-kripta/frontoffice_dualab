<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:80'],
            'color'  => ['sometimes', Rule::in(config('cms.colores_categoria'))],
        ];
    }
}
