<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** «Cómo nos ven Google y la IA»: solo direcciones de la web pública (ruta relativa, sin dominio). */
class VerComoBuscadoresRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ruta'   => ['required', 'string', 'max:200', 'regex:#^/[a-z0-9\-/]*$#'],
            'idioma' => ['nullable', 'string', 'max:5', 'regex:/^[a-z]{2}$/'],
        ];
    }

    public function messages(): array
    {
        return ['ruta.regex' => 'Esa dirección no es de la web pública.'];
    }
}
