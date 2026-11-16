<?php

namespace App\Http\Requests\Admin;

use App\Rules\UrlMedio;
use App\Models\Entrada;
use App\Services\SeoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntradaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idioma'           => ['required', 'string', 'exists:idiomas,codigo'],
            'titulo'           => ['required', 'string', 'max:200'],
            'slug'             => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'extracto'         => ['nullable', 'string', 'max:500'],
            'contenido'        => ['nullable', 'string', 'max:200000'],
            'imagen_destacada' => ['nullable', 'string', 'max:255', new UrlMedio],
            'categoria_id'     => ['nullable', 'integer', 'exists:categorias,id'],
            'estado'           => ['required', Rule::in($this->estadosPermitidos())],
            'publicada_en'     => ['nullable', 'date'],
            'destacada'        => ['sometimes', 'boolean'],
            ...SeoService::reglas(),
        ];
    }

    /** Un editor guarda borradores o los envía a revisión; publicar es cosa de un administrador. */
    private function estadosPermitidos(): array
    {
        return in_array($this->user()->role, config('cms.roles_admin'), true)
            ? Entrada::ESTADOS
            : ['borrador', 'pendiente'];
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'La URL solo puede llevar minúsculas, números y guiones.',
            'estado.in'   => 'Solo un administrador puede publicar. Envía la entrada a revisión.',
        ];
    }
}
