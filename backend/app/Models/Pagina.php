<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Página de la web pública. La estructura (secciones y campos) vive en
 * config/cms_paginas.php; aquí solo el estado y los textos cambiados.
 */
class Pagina extends Model
{
    protected $fillable = ['clave', 'publicada', 'idiomas_activos'];

    protected function casts(): array
    {
        return [
            'publicada'       => 'boolean',
            'idiomas_activos' => 'array',
        ];
    }

    public function contenidos(): HasMany
    {
        return $this->hasMany(ContenidoPagina::class);
    }

    public function seo(): MorphMany
    {
        return $this->morphMany(SeoMetadato::class, 'seoable');
    }
}
