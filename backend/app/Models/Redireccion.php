<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Redirección 301 de la web pública (ver EntradaService::redirigir). */
class Redireccion extends Model
{
    protected $table = 'redirecciones';

    protected $fillable = ['idioma', 'origen', 'destino'];
}
