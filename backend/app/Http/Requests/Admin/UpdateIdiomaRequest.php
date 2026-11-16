<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIdiomaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'activo'      => ['sometimes', 'boolean'],
            'por_defecto' => ['sometimes', 'accepted'],
            'orden'       => ['sometimes', 'integer', 'min:0', 'max:100'],
        ];
    }
}
