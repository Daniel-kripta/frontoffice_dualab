<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Adjunto de un correo. El archivo va al disco privado (`local`), nunca a uno público. */
class CorreoAdjunto extends Model
{
    public const DISCO = 'local';

    protected $fillable = ['nombre', 'tipo', 'tamano', 'en_linea', 'ruta'];

    protected $hidden = ['ruta'];

    protected function casts(): array
    {
        return ['en_linea' => 'boolean'];
    }

    public function mensaje()
    {
        return $this->belongsTo(CorreoMensaje::class, 'correo_mensaje_id');
    }

    public function borrarArchivo(): void
    {
        if ($this->ruta) {
            Storage::disk(self::DISCO)->delete($this->ruta);
        }
    }
}
