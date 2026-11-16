<?php

namespace App\Http\Requests\Admin;

use App\Models\Contacto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormularioRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(Contacto::ESTADOS)],
        ];
    }
}
