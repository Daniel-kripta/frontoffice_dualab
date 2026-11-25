<?php

namespace App\Services;

use App\Models\CorreoAdjunto;
use App\Models\CorreoMensaje;
use App\Models\CorreoSincronizacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Throwable;

/**
 * Copia a la BD lo nuevo de cada buzón (config/correo.php). Cada pasada:
 *  1. si cambió el UIDVALIDITY de la carpeta, descarta la copia y empieza de cero;
 *  2. quita de la copia lo que ya no está en Hostinger (borrado o movido desde el webmail);
 *  3. copia como mucho `lote` mensajes nuevos, de los últimos `retencion_meses`.
 * Nunca escribe en el buzón. En los logs solo van buzón, carpeta y UID, nunca direcciones.
 */
class CorreoSincronizacionService
{
    public function __construct(private CorreoImapService $imap) {}

    /** @return array<string, int|string> mensajes copiados por carpeta, o el error */
    public function sincronizar(string $buzon): array
    {
        $config = config("correo.buzones.{$buzon}");
        if (! $config || ! $config['password']) {
            return [];
        }

        $resultado = [];
        try {
            $this->imap->conectar($config);
            foreach (array_keys($config['carpetas']) as $carpeta) {
                $resultado[$carpeta] = $this->sincronizarCarpeta($buzon, $carpeta);
            }
        } catch (Throwable $e) {
            Log::warning('correo: fallo al sincronizar', ['buzon' => $buzon, 'error' => class_basename($e)]);
            foreach (array_keys($config['carpetas']) as $carpeta) {
                CorreoSincronizacion::updateOrCreate(['buzon' => $buzon, 'carpeta' => $carpeta], [
                    'ultimo_error' => 'No se ha podido conectar con el buzón. Se reintentará en la siguiente pasada.',
                    'error_en'     => now(),
                ]);
            }
            $resultado['error'] = class_basename($e);
        } finally {
            $this->imap->desconectar();
        }

        return $resultado;
    }

    private function sincronizarCarpeta(string $buzon, string $carpeta): int
    {
        $estado = CorreoSincronizacion::firstOrNew(['buzon' => $buzon, 'carpeta' => $carpeta]);
        $uidvalidity = $this->imap->uidvalidity($carpeta);

        if ($estado->uidvalidity !== null && (int) $estado->uidvalidity !== $uidvalidity) {
            $this->borrar(CorreoMensaje::where(['buzon' => $buzon, 'carpeta' => $carpeta]));
            $estado->fill(['ultimo_uid' => 0, 'uid_fallido' => null, 'intentos_fallidos' => 0, 'omitidos' => 0]);
        }
        $estado->uidvalidity = $uidvalidity;

        $enServidor = $this->imap->uidsDesde($carpeta, CorreoMensaje::fechaLimite());

        // Lo ya copiado que ha desaparecido del servidor (o salido de la ventana de retención)
        $this->borrar(CorreoMensaje::where(['buzon' => $buzon, 'carpeta' => $carpeta, 'uidvalidity' => $uidvalidity])
            ->where('uid', '<=', (int) $estado->ultimo_uid)
            ->whereNotIn('uid', $enServidor));

        $maxAdjunto = config('correo.max_adjunto_mb') * 1024 * 1024;
        $nuevos = array_slice(array_values(array_filter($enServidor, fn ($u) => $u > $estado->ultimo_uid)), 0, config('correo.lote'));

        $copiados = 0;
        foreach ($nuevos as $uid) {
            try {
                $this->guardar($buzon, $carpeta, $uidvalidity, $uid, $this->imap->leer($carpeta, $uid, $maxAdjunto));
                $copiados++;
                if ((int) $estado->uid_fallido === $uid) {
                    $estado->fill(['uid_fallido' => null, 'intentos_fallidos' => 0]);
                }
            } catch (Throwable $e) {
                Log::warning('correo: mensaje no copiado', ['buzon' => $buzon, 'carpeta' => $carpeta, 'uid' => $uid, 'error' => class_basename($e)]);
                if (! $this->saltarTrasFallo($estado, $uid)) {
                    break; // se reintenta en la siguiente pasada, sin perder el orden
                }
            }
            $estado->ultimo_uid = $uid;
        }

        if (self::claveCarpeta($buzon, $carpeta) === 'recibidos') {
            $this->traerLeidos($buzon, $carpeta, $uidvalidity);
        }

        $estado->fill(['sincronizado_en' => now(), 'ultimo_error' => null, 'error_en' => null])->save();

        return $copiados;
    }

    /**
     * Lo leído en el webmail pasa a leído en el panel. Solo en ese sentido: si en el panel se
     * marca como no leído, ese cambio va al buzón por la cola (MarcarLeidoEnBuzonJob) y aquí no
     * se deshace antes de que llegue.
     */
    private function traerLeidos(string $buzon, string $carpeta, int $uidvalidity): void
    {
        $sinLeer = CorreoMensaje::where(compact('buzon', 'carpeta', 'uidvalidity'))->where('estado', 'nuevo')->pluck('uid')->map(fn ($u) => (int) $u)->all();
        if (! $sinLeer) {
            return;
        }
        $leidos = array_keys(array_filter($this->imap->leidos($carpeta, $sinLeer)));
        if ($leidos) {
            CorreoMensaje::where(compact('buzon', 'carpeta', 'uidvalidity'))->where('estado', 'nuevo')->whereIn('uid', $leidos)
                ->update(['estado' => 'leido', 'leido_en' => now()]);
        }
    }

    /** Cuenta el fallo; al tercero seguido del mismo mensaje lo da por omitido (queda solo en el webmail). */
    private function saltarTrasFallo(CorreoSincronizacion $estado, int $uid): bool
    {
        $estado->intentos_fallidos = (int) $estado->uid_fallido === $uid ? $estado->intentos_fallidos + 1 : 1;
        $estado->uid_fallido = $uid;

        if ($estado->intentos_fallidos < 3) {
            return false;
        }
        $estado->fill(['omitidos' => $estado->omitidos + 1, 'uid_fallido' => null, 'intentos_fallidos' => 0]);

        return true;
    }

    /** Guarda un mensaje leído por CorreoImapService. Público para poder probarlo sin IMAP. */
    public function guardar(string $buzon, string $carpeta, int $uidvalidity, int $uid, array $datos): CorreoMensaje
    {
        $clave = compact('buzon', 'carpeta', 'uidvalidity', 'uid');
        if ($existente = CorreoMensaje::where($clave)->first()) {
            return $existente;
        }

        $html = $this->limpiarHtml($datos['html'] ?? null);
        $texto = $this->recortar($datos['texto'] ?? null);
        $adjuntos = $datos['adjuntos'] ?? [];

        $rutas = [];
        try {
            return DB::transaction(function () use ($clave, $datos, $html, $texto, $adjuntos, &$rutas) {
                $mensaje = CorreoMensaje::create($clave + [
                    'message_id'     => Str::limit((string) ($datos['message_id'] ?? ''), 250, '') ?: null,
                    'in_reply_to'    => Str::limit((string) ($datos['in_reply_to'] ?? ''), 250, '') ?: null,
                    'referencias'    => Str::limit((string) ($datos['referencias'] ?? ''), 5000, '') ?: null,
                    'de_email'       => Str::limit((string) ($datos['de_email'] ?? ''), 250, ''),
                    'de_nombre'      => Str::limit((string) ($datos['de_nombre'] ?? ''), 250, '') ?: null,
                    'para'           => $datos['para'] ?? [],
                    'cc'             => $datos['cc'] ?? [],
                    'responder_a'    => $datos['responder_a'] ?? [],
                    'asunto'         => Str::limit((string) ($datos['asunto'] ?? ''), 490) ?: null,
                    'fecha'          => $datos['fecha'] ?? now(),
                    'extracto'       => $this->extracto($texto, $html),
                    'texto'          => $texto,
                    'html'           => $html,
                    'tiene_adjuntos' => collect($adjuntos)->contains(fn ($a) => ! $a['en_linea']),
                ]);
                // Lo enviado no es correo nuevo por leer
                if (self::claveCarpeta($clave['buzon'], $clave['carpeta']) === 'enviados') {
                    $mensaje->forceFill(['estado' => 'leido', 'leido_en' => now()])->save();
                }

                foreach ($adjuntos as $a) {
                    $ruta = null;
                    if ($a['contenido'] !== null) {
                        // Nombre aleatorio en disco: el nombre original nunca forma parte de la ruta
                        $ruta = 'correo/'.$mensaje->id.'/'.Str::random(40);
                        Storage::disk(CorreoAdjunto::DISCO)->put($ruta, $a['contenido']);
                        $rutas[] = $ruta;
                    }
                    $mensaje->adjuntos()->create([
                        'nombre'   => Str::limit(str_replace(['/', '\\', "\0"], '_', $a['nombre']), 250, ''),
                        'tipo'     => Str::limit((string) $a['tipo'], 150, '') ?: null,
                        'tamano'   => $a['tamano'],
                        'en_linea' => $a['en_linea'],
                        'ruta'     => $ruta,
                    ]);
                }

                return $mensaje;
            });
        } catch (Throwable $e) {
            Storage::disk(CorreoAdjunto::DISCO)->delete($rutas);
            throw $e;
        }
    }

    /** «recibidos» / «enviados» según config/correo.php */
    public static function claveCarpeta(string $buzon, string $carpeta): ?string
    {
        return config("correo.buzones.{$buzon}.carpetas")[$carpeta] ?? null;
    }

    /** Ruta IMAP de una carpeta por su clave en el panel */
    public static function rutaCarpeta(string $buzon, string $clave): ?string
    {
        return array_search($clave, config("correo.buzones.{$buzon}.carpetas") ?? [], true) ?: null;
    }

    /** HTML del correo con lista blanca (perfil `correo`): sin scripts, formularios ni estilos globales. */
    private function limpiarHtml(?string $html): ?string
    {
        $html = $this->recortar($html);

        return $html === null ? null : (trim(Purifier::clean($html, 'correo')) ?: null);
    }

    private function recortar(?string $texto): ?string
    {
        if ($texto === null || trim($texto) === '') {
            return null;
        }

        return mb_strcut($texto, 0, config('correo.max_cuerpo_kb') * 1024);
    }

    private function extracto(?string $texto, ?string $html): ?string
    {
        $plano = $texto ?? html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5);
        $plano = trim(preg_replace('/\s+/u', ' ', $plano));

        return $plano === '' ? null : Str::limit($plano, 280);
    }

    /** Borra uno a uno para que se borren también los archivos de los adjuntos. */
    private function borrar($consulta): void
    {
        $consulta->with('adjuntos')->each(fn (CorreoMensaje $m) => $m->delete());
    }
}
