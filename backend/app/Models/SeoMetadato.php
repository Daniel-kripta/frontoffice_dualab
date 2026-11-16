<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMetadato extends Model
{
    protected $table = 'seo_metadatos';

    protected $fillable = ['idioma', 'meta_titulo', 'meta_descripcion', 'palabra_clave', 'imagen_og', 'canonical', 'noindex'];

    protected function casts(): array
    {
        return ['noindex' => 'boolean'];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
