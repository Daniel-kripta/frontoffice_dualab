<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdiomaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'codigo'      => $this->codigo,
            'nombre'      => $this->nombre,
            'activo'      => $this->activo,
            'por_defecto' => $this->por_defecto,
            'orden'       => $this->orden,
            'entradas'    => $this->when(isset($this->entradas_count), fn () => $this->entradas_count),
        ];
    }
}
