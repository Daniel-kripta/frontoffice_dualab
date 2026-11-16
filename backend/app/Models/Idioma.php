<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Idioma extends Model
{
    protected $fillable = ['codigo', 'nombre', 'activo', 'orden'];

    protected function casts(): array
    {
        return [
            'activo'      => 'boolean',
            'por_defecto' => 'boolean',
        ];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderByDesc('por_defecto')->orderBy('orden');
    }

    /** Código del idioma base (el que se usa si no se pide otro o el pedido no está activo). */
    public static function codigoPorDefecto(): string
    {
        return static::where('por_defecto', true)->value('codigo') ?? 'es';
    }

    /** Normaliza el idioma pedido por la web pública: si no está activo, el de por defecto. */
    public static function resolver(?string $codigo): string
    {
        if ($codigo && static::where('codigo', $codigo)->where('activo', true)->exists()) {
            return $codigo;
        }

        return static::codigoPorDefecto();
    }
}
