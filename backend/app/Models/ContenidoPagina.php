<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContenidoPagina extends Model
{
    protected $table = 'contenidos_pagina';

    protected $fillable = ['idioma', 'campo', 'valor'];
}
