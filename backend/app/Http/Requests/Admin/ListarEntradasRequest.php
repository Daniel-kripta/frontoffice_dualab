<?php

namespace App\Http\Requests\Admin;

use App\Models\Entrada;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarEntradasRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'estado'       => ['nullable', Rule::in([...Entrada::ESTADOS, 'programada', 'papelera'])],
            'idioma'       => ['nullable', 'string', 'exists:idiomas,codigo'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'buscar'       => ['nullable', 'string', 'max:100'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
