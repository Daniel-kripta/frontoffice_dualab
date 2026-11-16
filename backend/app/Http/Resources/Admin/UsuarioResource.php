<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Cuenta del panel tal como la ve el admin en Usuarios. */
class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'email'               => $this->email,
            'role'                => $this->role,
            'bloqueado'           => (bool) $this->blocked,
            // Sin activar (como en la app DuaLab): no puede iniciar sesión todavía
            'pendiente_activar'   => ! $this->email_verified_at,
            'ultimo_acceso_en'    => $this->ultimo_acceso_en,
            'creado_en'           => $this->created_at,
            'en_papelera_desde'   => $this->deleted_at,
            'eres_tu'             => $request->user()?->is($this->resource) ?? false,
        ];
    }
}
