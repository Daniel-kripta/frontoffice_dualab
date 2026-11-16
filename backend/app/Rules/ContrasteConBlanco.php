<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Color personalizado de la web (Apariencia) que tiene que contrastar con el blanco
 * (WCAG AA, ≥ 4,5:1): texto blanco sobre él (botones, franja) o él sobre fondo blanco
 * (títulos). El contraste es el mismo en los dos sentidos.
 */
class ContrasteConBlanco implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            return; // el formato lo valida la regex de config/cms.php
        }

        if (self::contraste($value) < 4.5) {
            $fail('Este color es demasiado claro: no se leería bien junto al blanco. Elige un tono más oscuro.');
        }
    }

    public static function contraste(string $hex): float
    {
        $canales = array_map(function ($c) {
            $c = hexdec($c) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));
        $luminancia = 0.2126 * $canales[0] + 0.7152 * $canales[1] + 0.0722 * $canales[2];

        return 1.05 / ($luminancia + 0.05);
    }
}
