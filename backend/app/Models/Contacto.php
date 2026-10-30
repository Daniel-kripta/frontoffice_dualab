<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    protected $fillable = ['nombre', 'apellidos', 'email', 'tipo', 'accion', 'telefono', 'cargo', 'datos'];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'crm_enviado_at' => 'datetime',
        ];
    }
}
