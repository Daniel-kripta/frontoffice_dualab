<?php

namespace App\Http\Requests\Admin;

use App\Models\ActividadPanel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarActividadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id'  => ['nullable', 'integer', 'min:1'],
            'seccion'  => ['nullable', Rule::in(array_keys(ActividadPanel::SECCIONES))],
            'desde'    => ['nullable', 'date'],
            'hasta'    => ['nullable', 'date', 'after_or_equal:desde'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
