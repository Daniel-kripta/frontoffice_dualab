<?php

namespace App\Http\Resources\Admin;

use App\Models\ActividadPanel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActividadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'usuario'     => ['id' => $this->user_id, 'nombre' => $this->usuario_nombre],
            'seccion'     => $this->seccion,
            'seccion_nombre' => ActividadPanel::SECCIONES[$this->seccion] ?? $this->seccion,
            'accion'      => $this->accion,
            'descripcion' => $this->descripcion,
            'objeto'      => $this->objeto_tipo ? ['tipo' => $this->objeto_tipo, 'ref' => $this->objeto_ref] : null,
            'fecha'       => $this->created_at,
        ];
    }
}
