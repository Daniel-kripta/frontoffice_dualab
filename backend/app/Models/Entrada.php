<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/** Entrada del blog/noticias. `contenido` se guarda ya purificado (ver EntradaService). */
class Entrada extends Model
{
    use SoftDeletes;

    // borrador → pendiente (enviada a revisión) → publicada (aprobada por un administrador)
    public const ESTADOS = ['borrador', 'pendiente', 'publicada'];

    protected $fillable = [
        'idioma', 'titulo', 'slug', 'extracto', 'contenido', 'imagen_destacada',
        'categoria_id', 'estado', 'publicada_en', 'destacada',
    ];

    protected function casts(): array
    {
        return [
            'publicada_en'        => 'datetime',
            'enviada_revision_en' => 'datetime',
            'aprobada_en'         => 'datetime',
            'destacada'    => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $olvidar = fn () => Cache::forget('web.sitemap');
        static::saved($olvidar);
        static::deleted($olvidar);
        static::restored($olvidar);
    }

    /** Visibles en la web: publicadas y con fecha ya alcanzada (las futuras quedan programadas). */
    public function scopeVisibles(Builder $query): Builder
    {
        return $query->where('estado', 'publicada')->where('publicada_en', '<=', now());
    }

    /** borrador | publicada | programada (publicada con fecha futura). */
    public function getEstadoVisibleAttribute(): string
    {
        return $this->estado === 'publicada' && $this->publicada_en?->isFuture() ? 'programada' : $this->estado;
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobada_por');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function seo(): MorphOne
    {
        // Una entrada es de un solo idioma: su SEO es el de ese idioma.
        return $this->morphOne(SeoMetadato::class, 'seoable');
    }

    /** Otras versiones de la misma entrada en otros idiomas. */
    public function traducciones()
    {
        return static::where('grupo_traduccion', $this->grupo_traduccion)->whereKeyNot($this->getKey());
    }
}
