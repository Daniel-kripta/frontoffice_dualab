<?php

namespace App\Services;

use App\Jobs\EnviarCorreoJob;
use App\Jobs\MarcarLeidoEnBuzonJob;
use App\Models\Contacto;
use App\Models\CorreoAdjunto;
use App\Models\CorreoEnvio;
use App\Models\CorreoMensaje;
use App\Models\User;
use App\Support\EnvioAlResponder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Throwable;

/**
 * Bandeja de salida del panel: guarda el envío y lo pone en la cola (EnviarCorreoJob).
 * Las respuestas llevan In-Reply-To y References para que el destinatario vea el hilo.
 */
class CorreoEnvioService
{
    /** @param UploadedFile[] $archivos */
    /** $usuario es null cuando el envío se pide desde DuaLab Central (queda en el registro de actividad). */
    public function crear(?User $usuario, array $datos, array $archivos): CorreoEnvio
    {
        $buzon = $datos['buzon'] ?? 'info';
        $original = isset($datos['correo_id']) ? CorreoMensaje::find($datos['correo_id']) : null;
        $dominio = Str::after(config("correo.buzones.{$buzon}.email"), '@');

        $hilo = [];
        if ($original && $datos['tipo'] === 'responder' && $original->message_id) {
            $hilo = [
                'in_reply_to' => $original->message_id,
                'referencias' => trim(($original->referencias ? $original->referencias.' ' : '').$original->message_id),
            ];
        }

        $rutas = [];
        try {
            $envio = DB::transaction(function () use ($usuario, $datos, $buzon, $original, $dominio, $hilo, $archivos, &$rutas) {
                $envio = CorreoEnvio::create([
                    'user_id'           => $usuario?->id,
                    'buzon'             => $buzon,
                    'tipo'              => $datos['tipo'],
                    'correo_mensaje_id' => $original?->id,
                    'contacto_id'       => $datos['contacto_id'] ?? null,
                    'para'              => array_values(array_unique($datos['para'])),
                    'cc'                => array_values(array_unique($datos['cc'] ?? [])),
                    'cco'               => array_values(array_unique($datos['cco'] ?? [])),
                    'asunto'            => $datos['asunto'],
                    'texto'             => $datos['texto'],
                    // Versión maquetada (solo desde Central): limpia, como el HTML de los correos que llegan
                    'html'              => filled($datos['html'] ?? null) ? (trim(Purifier::clean($datos['html'], 'correo')) ?: null) : null,
                    'message_id'        => '<'.Str::uuid().'@'.$dominio.'>',
                ] + $hilo);

                foreach ($archivos as $archivo) {
                    $ruta = $archivo->storeAs('correo-envios/'.$envio->id, Str::random(40), CorreoAdjunto::DISCO);
                    $rutas[] = $ruta;
                    $envio->adjuntos()->create([
                        'nombre' => $this->nombreSeguro($archivo->getClientOriginalName()),
                        'tipo'   => $archivo->getMimeType(),
                        'tamano' => $archivo->getSize(),
                        'ruta'   => $ruta,
                    ]);
                }

                // Reenvío: copia de los adjuntos del original (el FormRequest ya comprobó que son suyos)
                if ($original && ! empty($datos['reenviar_adjuntos'])) {
                    foreach ($original->adjuntos()->whereIn('id', $datos['reenviar_adjuntos'])->whereNotNull('ruta')->get() as $a) {
                        $ruta = 'correo-envios/'.$envio->id.'/'.Str::random(40);
                        Storage::disk(CorreoAdjunto::DISCO)->copy($a->ruta, $ruta);
                        $rutas[] = $ruta;
                        $envio->adjuntos()->create(['nombre' => $a->nombre, 'tipo' => $a->tipo, 'tamano' => $a->tamano, 'ruta' => $ruta]);
                    }
                }

                return $envio;
            });
        } catch (Throwable $e) {
            Storage::disk(CorreoAdjunto::DISCO)->delete($rutas);
            throw $e;
        }

        // Responder deja el original como leído (y lo saca de «No leídos»)
        if ($original && $original->estado === 'nuevo') {
            $original->forceFill(['estado' => 'leido', 'leido_en' => now()])->save();
            MarcarLeidoEnBuzonJob::siProcede($original, true);
        }

        // Responder a un formulario lo da por leído en su bandeja
        if (! empty($datos['contacto_id'])) {
            Contacto::whereKey($datos['contacto_id'])->where('estado', 'nuevo')->update(['estado' => 'leido', 'leido_en' => now()]);
        }

        // Sin esperar al cron de la cola; si el SMTP falla, a la cola con sus reintentos
        EnvioAlResponder::despachar(new EnviarCorreoJob($envio->id));

        return $envio;
    }

    public function reintentar(CorreoEnvio $envio): void
    {
        abort_unless($envio->estado === 'error', 422, 'Solo se pueden reintentar los envíos que han fallado.');
        $envio->forceFill(['estado' => 'pendiente', 'error' => null])->save();
        EnvioAlResponder::despachar(new EnviarCorreoJob($envio->id));
    }

    public function descartar(CorreoEnvio $envio): void
    {
        abort_unless($envio->estado === 'error', 422, 'Solo se pueden descartar los envíos que han fallado.');
        $envio->delete();
    }

    private function nombreSeguro(string $nombre): string
    {
        return Str::limit(str_replace(['/', '\\', "\0", "\r", "\n"], '_', $nombre), 200, '') ?: 'adjunto';
    }
}
