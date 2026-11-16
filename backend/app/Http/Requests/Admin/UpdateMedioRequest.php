<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedioRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'alt'    => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
