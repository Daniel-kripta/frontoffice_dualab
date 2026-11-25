<?php

namespace App\Models;

use App\Services\CorreoImapService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * Copia local de un correo de un buzón de Hostinger (ver CorreoSincronizacionService).
 * Se borra a los config('correo.retencion_meses'): el original sigue en el webmail.
 */
class CorreoMensaje extends Model
{
    use Prunable;

    // papelera: lo quitó DuaLab Central; solo la copia local (el original sigue en el webmail)
    public const ESTADOS = ['nuevo', 'leido', 'archivado', 'papelera'];

    // Todo lo pone la sincronización; `estado` y `leido_en` solo los cambia el panel (forceFill)
    protected $fillable = [
        'buzon', 'carpeta', 'uidvalidity', 'uid', 'message_id', 'in_reply_to', 'referencias',
        'de_email', 'de_nombre', 'para', 'cc', 'responder_a', 'asunto', 'fecha', 'extracto',
        'texto', 'html', 'tiene_adjuntos',
    ];

    protected function casts(): array
    {
        return [
            'para'           => 'array',
            'cc'             => 'array',
            'responder_a'    => 'array',
            'fecha'          => 'datetime',
            'leido_en'       => 'datetime',
            'tiene_adjuntos' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Los adjuntos se borran en cascada en la BD; sus archivos, aquí
        static::deleting(fn (CorreoMensaje $m) => $m->adjuntos->each->borrarArchivo());
    }

    // Lo copiado antes de descodificar las cabeceras al sincronizar se muestra igual de limpio
    protected function asunto(): Attribute
    {
        return Attribute::get(fn (?string $v) => CorreoImapService::cabecera($v));
    }

    protected function deNombre(): Attribute
    {
        return Attribute::get(fn (?string $v) => CorreoImapService::cabecera($v));
    }

    public function adjuntos()
    {
        return $this->hasMany(CorreoAdjunto::class);
    }

    public static function fechaLimite()
    {
        return now()->subMonths(config('correo.retencion_meses'))->startOfDay();
    }

    public function prunable(): Builder
    {
        return static::where('fecha', '<', static::fechaLimite());
    }
}
