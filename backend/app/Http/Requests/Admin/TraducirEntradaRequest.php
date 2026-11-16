<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TraducirEntradaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idioma' => ['required', 'string', 'exists:idiomas,codigo', Rule::notIn([$this->route('entrada')->idioma])],
        ];
    }
}
