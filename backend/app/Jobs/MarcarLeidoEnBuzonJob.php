<?php

namespace App\Jobs;

use App\Models\CorreoMensaje;
use App\Services\CorreoImapService;
use App\Services\CorreoSincronizacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lleva al buzón (\Seen) lo que se marca en el panel: abrir un correo lo marca como leído en el
 * webmail y «Marcar como no leído» lo desmarca. Va por cola: el panel no espera a Hostinger.
 */
class MarcarLeidoEnBuzonJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public int $correoId, public bool $leido) {}

    /** Solo lo recibido tiene «leído» en el panel; en local va desactivado (CORREO_MARCAR_EN_BUZON=false). */
    public static function siProcede(CorreoMensaje $correo, bool $leido): void
    {
        if (config("correo.buzones.{$correo->buzon}.marcar_en_buzon")
            && CorreoSincronizacionService::claveCarpeta($correo->buzon, $correo->carpeta) === 'recibidos') {
            self::dispatch($correo->id, $leido);
        }
    }

    public function handle(CorreoImapService $imap): void
    {
        $correo = CorreoMensaje::find($this->correoId);
        $config = $correo ? config("correo.buzones.{$correo->buzon}") : null;
        if (! $correo || ! $config || ! $config['password']) {
            return;
        }

        try {
            $imap->conectar($config);
            // Si el UIDVALIDITY cambió, el UID ya no es de este mensaje: no se toca nada
            if ($imap->uidvalidity($correo->carpeta) === (int) $correo->uidvalidity) {
                $imap->marcarLeido($correo->carpeta, (int) $correo->uid, $this->leido);
            }
        } finally {
            $imap->desconectar();
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::warning('correo: no se pudo marcar en el buzón', ['correo' => $this->correoId, 'error' => $e ? class_basename($e) : null]);
    }
}
