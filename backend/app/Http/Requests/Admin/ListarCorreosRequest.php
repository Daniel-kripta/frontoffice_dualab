<?php

namespace App\Http\Requests\Admin;

use App\Models\CorreoMensaje;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarCorreosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'buzon'    => ['nullable', Rule::in(array_keys(config('correo.buzones')))],
            'carpeta'  => ['nullable', Rule::in(['recibidos', 'enviados'])],
            'estado'   => ['nullable', Rule::in(CorreoMensaje::ESTADOS)],
            'buscar'   => ['nullable', 'string', 'max:100'],
            'adjuntos' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            // Bandejas aparte en DuaLab Central (p. ej. «Respuestas de solicitudes» de retos): solo los
            // correos cuyo asunto contiene un texto O que llegan de alguno de estos remitentes, o
            // al revés, todos menos esos (para que el Correo de info@ no los repita)
            'incluir_asunto' => ['nullable', 'string', 'max:100'],
            'incluir_de'     => ['nullable', 'array', 'max:200'],
            'incluir_de.*'   => ['string', 'max:255'],
            'excluir_asunto' => ['nullable', 'string', 'max:100'],
            'excluir_de'     => ['nullable', 'array', 'max:200'],
            'excluir_de.*'   => ['string', 'max:255'],
        ];
    }
}
