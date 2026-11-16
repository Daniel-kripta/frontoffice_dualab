<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DevolverEntradaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nota' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['nota.required' => 'Explica qué hay que cambiar para que el autor lo sepa.'];
    }
}
