<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarMediosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tipo'     => ['nullable', Rule::in(array_keys(config('cms.medios.tipos')))],
            'buscar'   => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
