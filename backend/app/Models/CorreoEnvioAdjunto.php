<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Adjunto de un correo enviado desde el panel. Disco privado, nombre aleatorio en disco. */
class CorreoEnvioAdjunto extends Model
{
    protected $fillable = ['nombre', 'tipo', 'tamano', 'ruta'];

    protected $hidden = ['ruta'];

    public function borrarArchivo(): void
    {
        Storage::disk(CorreoAdjunto::DISCO)->delete($this->ruta);
    }
}
