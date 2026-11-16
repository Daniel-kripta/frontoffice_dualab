<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'nombre'   => $this->nombre,
            'slug'     => $this->slug,
            'color'    => $this->color,
            'entradas' => $this->whenCounted('entradas'),
        ];
    }
}
