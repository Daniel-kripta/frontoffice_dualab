<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'url'             => $this->url,
            'tipo'            => $this->tipo,
            'mime'            => $this->mime,
            'nombre_original' => $this->nombre_original,
            'titulo'          => $this->titulo,
            'alt'             => $this->alt,
            'tamano'          => $this->tamano,
            'ancho'           => $this->ancho,
            'alto'            => $this->alto,
            'autor'           => $this->whenLoaded('autor', fn () => $this->autor?->name),
            'created_at'      => $this->created_at,
        ];
    }
}
