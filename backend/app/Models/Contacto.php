<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    protected $fillable = [
        'nombre', 'apellidos', 'email', 'tipo', 'accion', 'telefono', 'cargo', 'datos',
        'origen', 'marketing_consent_at',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'origen' => 'array',
            'marketing_consent_at' => 'datetime',
            'crm_enviado_at' => 'datetime',
        ];
    }
}
