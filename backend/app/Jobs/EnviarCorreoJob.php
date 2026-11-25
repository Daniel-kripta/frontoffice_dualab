<?php

namespace App\Jobs;

use App\Models\CorreoAdjunto;
use App\Models\CorreoEnvio;
use App\Services\CorreoImapService;
use App\Services\CorreoSincronizacionService;
use App\Services\CorreoSmtpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Envía un correo de la bandeja de salida por el SMTP del buzón y guarda una copia en su
 * carpeta de enviados. Va por cola: el panel no espera al servidor de correo.
 * Una vez enviado no se vuelve a enviar aunque falle la copia (nunca duplicados).
 */
class EnviarCorreoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public int $envioId) {}

    public function handle(CorreoSmtpService $smtp, CorreoImapService $imap): void
    {
        $envio = CorreoEnvio::with('adjuntos')->find($this->envioId);
        if (! $envio || $envio->estado === 'enviado') {
            return;
        }
        $buzon = config("correo.buzones.{$envio->buzon}");

        $mime = $smtp->enviar($buzon, $this->mensaje($envio, $buzon));
        $envio->forceFill(['estado' => 'enviado', 'error' => null, 'enviado_en' => now()])->save();

        if ($buzon['guardar_en_enviados'] && ($carpeta = CorreoSincronizacionService::rutaCarpeta($envio->buzon, 'enviados'))) {
            try {
                $imap->conectar($buzon);
                $imap->guardarEnviado($carpeta, $mime);
                $envio->forceFill(['copia_en_enviados' => true])->save();
            } catch (Throwable $e) {
                Log::warning('correo: enviado sin copia en Enviados', ['envio' => $envio->id, 'error' => class_basename($e)]);
            } finally {
                $imap->desconectar();
            }
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::warning('correo: envío fallido', ['envio' => $this->envioId, 'error' => $e ? class_basename($e) : null]);
        CorreoEnvio::whereKey($this->envioId)->where('estado', '!=', 'enviado')->update([
            'estado' => 'error',
            'error'  => 'No se ha podido enviar. Revisa las direcciones y vuelve a intentarlo, o envíalo desde el webmail.',
        ]);
    }

    private function mensaje(CorreoEnvio $envio, array $buzon): Email
    {
        // Con versión maquetada (envíos de Central con diseño propio), su texto ya lleva despedida y pie
        $texto = $envio->html ? rtrim($envio->texto) : rtrim($envio->texto)."\n\n".$buzon['firma'];
        $email = (new Email())
            ->from(new Address($buzon['email'], $buzon['nombre']))
            ->to(...$envio->para)
            ->subject($envio->asunto)
            ->text($texto)
            ->html($envio->html ?: $this->html($texto));
        if ($envio->cc) {
            $email->cc(...$envio->cc);
        }
        if ($envio->cco) {
            $email->bcc(...$envio->cco);
        }

        $cabeceras = $email->getHeaders();
        $cabeceras->addIdHeader('Message-ID', trim($envio->message_id, '<>'));
        if ($envio->in_reply_to) {
            $cabeceras->addIdHeader('In-Reply-To', trim($envio->in_reply_to, '<>'));
            $cabeceras->addIdHeader('References', array_map(fn ($id) => trim($id, '<>'), preg_split('/\s+/', trim($envio->referencias))));
        }

        foreach ($envio->adjuntos as $a) {
            $email->attach(Storage::disk(CorreoAdjunto::DISCO)->get($a->ruta), $a->nombre, $a->tipo ?: 'application/octet-stream');
        }

        return $email;
    }

    /** Versión HTML del texto: escapado, saltos de línea y las citas («> …») en gris. */
    private function html(string $texto): string
    {
        $lineas = array_map(function ($l) {
            $escapada = htmlspecialchars($l, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return str_starts_with(ltrim($l), '>') ? '<span style="color:#6b7280">'.$escapada.'</span>' : $escapada;
        }, preg_split('/\r\n|\r|\n/', $texto));

        return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#1e2a3b">'
            .implode("<br>\n", $lineas).'</div>';
    }
}
