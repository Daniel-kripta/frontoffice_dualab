<?php

namespace App\Http\Resources\Admin;

use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntradaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'idioma'           => $this->idioma,
            'grupo_traduccion' => $this->grupo_traduccion,
            'titulo'           => $this->titulo,
            'slug'             => $this->slug,
            'extracto'         => $this->extracto,
            'contenido'        => $this->when($request->routeIs('admin.entradas.show', 'admin.entradas.store', 'admin.entradas.update'), $this->contenido),
            'imagen_destacada' => $this->imagen_destacada,
            'categoria'        => new CategoriaResource($this->whenLoaded('categoria')),
            'categoria_id'     => $this->categoria_id,
            'estado'           => $this->estado_visible,
            'publicada_en'     => $this->publicada_en,
            'destacada'        => $this->destacada,
            'enviada_revision_en' => $this->enviada_revision_en,
            'aprobada_en'      => $this->aprobada_en,
            'aprobada_por'     => $this->whenLoaded('aprobador', fn () => $this->aprobador?->name),
            'nota_revision'    => $this->nota_revision,
            // Lo que puede hacer quien mira (la Policy decide; el panel solo adapta los botones)
            'permisos'         => [
                'editar'  => $request->user()?->can('update', $this->resource) ?? false,
                'aprobar' => $request->user()?->can('aprobar', \App\Models\Entrada::class) ?? false,
            ],
            'autor'            => $this->whenLoaded('autor', fn () => $this->autor?->name),
            'seo'              => $this->whenLoaded('seo', fn () => app(SeoService::class)->paraPanel($this->seo)),
            'en_papelera'      => $this->trashed(),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
