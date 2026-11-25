<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * Correo enviado desde el panel (bandeja de salida). Se borra a los
 * config('correo.retencion_meses'); la copia sigue en «Enviados» de Hostinger.
 */
class CorreoEnvio extends Model
{
    use Prunable;

    public const TIPOS = ['nuevo', 'responder', 'reenviar'];

    // Lo rellena CorreoEnvioService desde datos validados; estado, error y fechas solo los cambia el job (forceFill)
    protected $fillable = [
        'user_id', 'buzon', 'tipo', 'correo_mensaje_id', 'contacto_id', 'para', 'cc', 'cco', 'asunto', 'texto', 'html',
        'message_id', 'in_reply_to', 'referencias',
    ];

    protected function casts(): array
    {
        return [
            'para'              => 'array',
            'cc'                => 'array',
            'cco'               => 'array',
            'copia_en_enviados' => 'boolean',
            'enviado_en'        => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn (CorreoEnvio $e) => $e->adjuntos->each->borrarArchivo());
    }

    public function adjuntos()
    {
        return $this->hasMany(CorreoEnvioAdjunto::class);
    }

    public function original()
    {
        return $this->belongsTo(CorreoMensaje::class, 'correo_mensaje_id');
    }

    /** Formulario al que responde (bandeja de Formularios) */
    public function contacto()
    {
        return $this->belongsTo(Contacto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', CorreoMensaje::fechaLimite());
    }
}
