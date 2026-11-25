<?php

namespace App\Http\Requests\Admin;

use App\Models\CorreoMensaje;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCorreoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(CorreoMensaje::ESTADOS)],
        ];
    }
}
