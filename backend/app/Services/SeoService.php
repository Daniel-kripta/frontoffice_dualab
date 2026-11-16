<?php

namespace App\Services;

use App\Rules\UrlMedio;
use App\Models\Ajuste;
use App\Models\SeoMetadato;
use Illuminate\Database\Eloquent\Model;

/** SEO por página/entrada e idioma (equivalente a la caja de AIOSEO). */
class SeoService
{
    private const CAMPOS = ['meta_titulo', 'meta_descripcion', 'palabra_clave', 'imagen_og', 'canonical', 'noindex'];

    /** Reglas comunes de validación (prefijo = clave del array en el request). */
    public static function reglas(string $prefijo = 'seo'): array
    {
        return [
            $prefijo                       => ['sometimes', 'array'],
            "$prefijo.meta_titulo"         => ['nullable', 'string', 'max:120'],
            "$prefijo.meta_descripcion"    => ['nullable', 'string', 'max:320'],
            "$prefijo.palabra_clave"       => ['nullable', 'string', 'max:80'],
            "$prefijo.imagen_og"           => ['nullable', 'string', 'max:255', new UrlMedio],
            "$prefijo.canonical"           => ['nullable', 'url:https', 'max:255'],
            "$prefijo.noindex"             => ['sometimes', 'boolean'],
        ];
    }

    public function guardar(Model $modelo, string $idioma, array $datos): void
    {
        $valores = collect($datos)->only(self::CAMPOS)
            ->map(fn ($v, $k) => $k === 'noindex' ? (bool) $v : (trim((string) $v) ?: null));

        $vacio = $valores->except('noindex')->filter()->isEmpty() && ! $valores->get('noindex');

        if ($vacio) {
            $modelo->seo()->where('idioma', $idioma)->delete();

            return;
        }

        $modelo->seo()->updateOrCreate(['idioma' => $idioma], $valores->all());
    }

    public function paraPanel(?SeoMetadato $seo): array
    {
        return collect(self::CAMPOS)->mapWithKeys(fn ($c) => [$c => $seo?->$c ?? ($c === 'noindex' ? false : null)])->all();
    }

    /** SEO que usa la web pública: lo propio, completado con los ajustes globales. */
    public function paraPublico(?SeoMetadato $seo): array
    {
        return [
            'meta_titulo'      => $seo?->meta_titulo,
            'meta_descripcion' => $seo?->meta_descripcion ?: Ajuste::leer('seo.descripcion'),
            'imagen_og'        => $seo?->imagen_og ?: Ajuste::leer('seo.imagen_og'),
            'canonical'        => $seo?->canonical,
            'noindex'          => (bool) $seo?->noindex || Ajuste::leer('seo.indexar', true) === false,
        ];
    }
}
