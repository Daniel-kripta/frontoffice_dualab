<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Destino de un botón o enlace editable: ruta interna (/contacto?tipo=centro),
 * ancla (#servicios), https://, mailto: o tel:. Nunca javascript:, data: ni
 * rutas de protocolo relativo (//otro-dominio).
 */
class EnlaceSeguro implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $valor = trim((string) $value);

        $valido = preg_match('#^/(?!/)[^\s]*$#', $valor)
            || preg_match('#^\#[\w\-]+$#', $valor)
            || (preg_match('#^https://[^\s]+$#i', $valor) && filter_var($valor, FILTER_VALIDATE_URL))
            || preg_match('#^mailto:[^\s@]+@[^\s@]+$#i', $valor)
            || preg_match('#^tel:\+?[0-9 ]{6,20}$#', $valor);

        if (! $valido) {
            $fail('El enlace debe ser una página de la web (/contacto), un ancla (#seccion), una dirección https://, mailto: o tel:.');
        }
    }
}
