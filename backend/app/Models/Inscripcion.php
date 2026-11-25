<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    // Sin esto Laravel pluraliza en inglés («inscripcions») y /api/auth/me da 500
    protected $table = 'inscripciones';

    protected $fillable = ['user_id', 'reto_id', 'estado'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reto()
    {
        return $this->belongsTo(Reto::class);
    }
}
