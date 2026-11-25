<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password'];

    protected $attributes = ['role' => 'alumno'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'blocked'            => 'boolean',
        'ultimo_acceso_en'   => 'datetime',
    ];

    /** Rol del panel de gestión web (admin, editor) */
    public function esDelPanel(): bool
    {
        return in_array($this->role, config('cms.roles_panel'), true);
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }
}
