<?php

namespace App\Http\Resources\Web;

use App\Services\CloudinaryService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Entrada tal como la ve la web pública: sin autor interno, estado ni datos del panel. */
class EntradaPublicaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detalle = $request->routeIs('web.entradas.show');

        return [
            'titulo'           => $this->titulo,
            'slug'             => $this->slug,
            'idioma'           => $this->idioma,
            'extracto'         => $this->extracto,
            'contenido'        => $this->when($detalle, fn () => CloudinaryService::optimizarHtml($this->contenido)),
            // En el listado las fotos van en tarjeta: con 800 px sobra
            'imagen_destacada' => CloudinaryService::optimizar($this->imagen_destacada, $detalle ? 1600 : 800),
            'categoria'        => $this->whenLoaded('categoria', fn () => $this->categoria ? [
                'nombre' => $this->categoria->nombre,
                'slug'   => $this->categoria->slug,
                'color'  => $this->categoria->color,
            ] : null),
            'publicada_en'     => $this->publicada_en,
            'destacada'        => $this->destacada,
            'minutos_lectura'  => max(1, (int) ceil(str_word_count(strip_tags((string) $this->contenido)) / 200)),
            'seo'              => $this->when($request->routeIs('web.entradas.show'), fn () => app(SeoService::class)->paraPublico($this->seo)),
        ];
    }
}
