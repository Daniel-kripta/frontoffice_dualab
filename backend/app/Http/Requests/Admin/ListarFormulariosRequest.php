<?php

namespace App\Http\Requests\Admin;

use App\Models\Contacto;
use App\Services\BandejaFormulariosService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarFormulariosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'estado'        => ['nullable', Rule::in(Contacto::ESTADOS)],
            'formulario'    => ['nullable', Rule::in(array_keys(BandejaFormulariosService::FORMULARIOS))],
            'accion'        => ['nullable', Rule::in(array_keys(BandejaFormulariosService::ACCIONES))],
            'pagina_origen' => ['nullable', 'string', 'max:255'],
            'buscar'        => ['nullable', 'string', 'max:100'],
            'desde'         => ['nullable', 'date'],
            'hasta'         => ['nullable', 'date', 'after_or_equal:desde'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
