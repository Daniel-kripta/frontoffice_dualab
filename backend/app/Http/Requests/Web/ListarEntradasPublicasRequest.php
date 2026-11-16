<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ListarEntradasPublicasRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idioma'    => ['nullable', 'string', 'max:5'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }
}
