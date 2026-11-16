<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Imagen editable: archivo de la biblioteca de medios, una ruta de la web (/...) o una URL https. */
class UrlMedio implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $valor      = trim((string) $value);
        $biblioteca = Storage::disk(config('cms.medios.disco'))->url('medios/');

        $valido = Str::startsWith($valor, $biblioteca)
            || preg_match('#^/(?!/)[^\s]*$#', $valor)
            || (preg_match('#^https://[^\s]+$#i', $valor) && filter_var($valor, FILTER_VALIDATE_URL));

        if (! $valido) {
            $fail('La imagen debe elegirse de la biblioteca de medios o ser una dirección https://.');
        }
    }
}
