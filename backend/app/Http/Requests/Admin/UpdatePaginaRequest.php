<?php

namespace App\Http\Requests\Admin;

use App\Rules\UrlMedio;
use App\Models\Idioma;
use App\Rules\EnlaceSeguro;
use App\Services\PaginaService;
use App\Services\SeoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Reglas generadas desde el esquema de la página (config/cms_paginas.php): solo sus campos. */
class UpdatePaginaRequest extends FormRequest
{
    public function rules(): array
    {
        $campos = app(PaginaService::class)->campos($this->route('clave'));
        $idiomas = Idioma::pluck('codigo')->all();

        $reglas = [
            'idioma'            => ['required', Rule::in($idiomas)],
            'publicada'         => ['sometimes', 'boolean'],
            'idiomas_activos'   => ['sometimes', 'nullable', 'array'],
            'idiomas_activos.*' => ['distinct', Rule::in($idiomas)],
            ...SeoService::reglas(),
        ];

        // valores: { seccion: { campo: valor } } — lista blanca a los dos niveles.
        $porSeccion = collect($campos)->groupBy(fn ($d, $clave) => strtok($clave, '.'), true);
        $reglas['valores'] = ['sometimes', 'array:'.$porSeccion->keys()->implode(',')];

        foreach ($porSeccion as $seccion => $camposSeccion) {
            $nombres = collect($camposSeccion)->keys()->map(fn ($c) => substr($c, strlen($seccion) + 1));
            $reglas["valores.$seccion"] = ['sometimes', 'array:'.$nombres->implode(',')];

            foreach ($camposSeccion as $clave => $def) {
                $k = "valores.$clave";
                $reglas += match ($def['tipo']) {
                    'texto'       => [$k => ['nullable', 'string', 'max:'.($def['max'] ?? 200)]],
                    'texto_largo' => [$k => ['nullable', 'string', 'max:'.($def['max'] ?? 2000)]],
                    'imagen'      => [$k => ['nullable', 'string', 'max:255', new UrlMedio]],
                    'galeria'     => [
                        $k     => ['nullable', 'array', 'max:'.($def['max'] ?? 40)],
                        "$k.*" => ['string', 'max:255', new UrlMedio],
                    ],
                    'boton'       => [
                        $k         => ['nullable', 'array:texto,url'],
                        "$k.texto" => ['nullable', 'string', 'max:60'],
                        "$k.url"   => ['nullable', 'string', 'max:255', new EnlaceSeguro],
                    ],
                };
            }
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'valores.array'   => 'Hay secciones que no pertenecen a esta página.',
            'valores.*.array' => 'Hay campos que no pertenecen a esta página.',
        ];
    }
}
