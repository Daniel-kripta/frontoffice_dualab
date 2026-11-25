<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Hasta dónde se ha copiado cada carpeta de cada buzón (último UID) y cómo fue la última pasada. */
class CorreoSincronizacion extends Model
{
    protected $table = 'correo_sincronizaciones';

    // Solo la escribe CorreoSincronizacionService
    protected $fillable = [
        'buzon', 'carpeta', 'uidvalidity', 'ultimo_uid', 'uid_fallido', 'intentos_fallidos', 'omitidos',
        'sincronizado_en', 'ultimo_error', 'error_en',
    ];

    protected function casts(): array
    {
        return [
            'sincronizado_en' => 'datetime',
            'error_en'        => 'datetime',
        ];
    }
}
