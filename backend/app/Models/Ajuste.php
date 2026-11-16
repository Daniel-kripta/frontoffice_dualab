<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Ajuste del sitio (clave/valor). Las claves permitidas y cuáles son secretas
 * están en config/cms.php (`ajustes`); las secretas se guardan cifradas.
 */
class Ajuste extends Model
{
    protected $fillable = ['clave', 'valor'];

    protected $hidden = ['valor'];

    public static function definicion(string $clave): ?array
    {
        // Acceso directo: la clave lleva punto (seo.titulo_sitio) y config() lo trataría como anidado.
        return config('cms.ajustes')[$clave] ?? null;
    }

    public static function leer(string $clave, mixed $defecto = null): mixed
    {
        $fila = static::where('clave', $clave)->first();
        if (! $fila || $fila->valor === null) {
            return $defecto;
        }

        $valor = ! empty(static::definicion($clave)['secreto']) ? Crypt::decryptString($fila->valor) : $fila->valor;

        return json_decode($valor, true) ?? $defecto;
    }

    public static function guardar(string $clave, mixed $valor): void
    {
        if ($valor === null || $valor === '') {
            static::where('clave', $clave)->delete();

            return;
        }

        $json = json_encode($valor);
        static::updateOrCreate(['clave' => $clave], [
            'valor' => ! empty(static::definicion($clave)['secreto']) ? Crypt::encryptString($json) : $json,
        ]);
    }
}
