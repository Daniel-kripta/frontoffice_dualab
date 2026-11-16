<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    public const ESTADOS = ['nuevo', 'leido', 'archivado'];

    // `estado` y `leido_en` solo los cambia el panel (forceFill en ContactoAdminService).
    protected $fillable = [
        'nombre', 'apellidos', 'email', 'tipo', 'accion', 'telefono', 'cargo', 'datos', 'pagina_origen',
        'origen', 'marketing_consent_at',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'origen' => 'array',
            'marketing_consent_at' => 'datetime',
            'crm_enviado_at' => 'datetime',
            'leido_en' => 'datetime',
        ];
    }
}
