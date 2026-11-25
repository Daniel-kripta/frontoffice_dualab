<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Webklex\PHPIMAP\Address;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\IMAP;

/**
 * Lectura de un buzón por IMAP (webklex/php-imap, sin ext-imap). Los mensajes se piden con
 * FT_PEEK, así que nada se marca como leído en Hostinger; solo se escribe al guardar en
 * «Enviados» lo que sale del panel (guardarEnviado).
 * Devuelve arrays simples para que CorreoSincronizacionService no dependa de la librería.
 */
class CorreoImapService
{
    private ?Client $cliente = null;

    /** @var array<string, Folder> */
    private array $carpetas = [];

    public function conectar(array $buzon): void
    {
        $this->cliente = (new ClientManager(['options' => ['fetch' => IMAP::FT_PEEK]]))->make([
            'host'          => $buzon['host'],
            'port'          => $buzon['puerto'],
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => $buzon['usuario'],
            'password'      => $buzon['password'],
            'protocol'      => 'imap',
            'timeout'       => 20,
        ]);
        $this->cliente->connect();
    }

    public function desconectar(): void
    {
        $this->cliente?->disconnect();
        $this->cliente = null;
        $this->carpetas = [];
    }

    /** UIDVALIDITY de la carpeta: si cambia, los UID guardados ya no valen. */
    public function uidvalidity(string $ruta): int
    {
        return (int) ($this->carpeta($ruta)->examine()['uidvalidity'] ?? 0);
    }

    /** UID de los mensajes recibidos desde `$desde` (fecha interna del servidor), de menor a mayor. */
    public function uidsDesde(string $ruta, CarbonInterface $desde): array
    {
        $uids = $this->carpeta($ruta)->query()->whereSince($desde)->search()->map(fn ($u) => (int) $u)->all();
        sort($uids);

        return $uids;
    }

    /**
     * Un mensaje como array: cabeceras, cuerpos sin limpiar y adjuntos con su contenido.
     * Los adjuntos más grandes que `$maxAdjunto` bytes van sin contenido.
     */
    public function leer(string $ruta, int $uid, int $maxAdjunto): array
    {
        $m = $this->carpeta($ruta)->query()->leaveUnread()->getMessageByUid($uid);

        $de = $m->getFrom()->first();
        $fecha = $m->getDate()->first();

        return [
            'message_id'  => $this->texto($m->getMessageId()->first()),
            'in_reply_to' => $this->texto($m->getInReplyTo()->first()),
            'referencias' => $this->texto(implode(' ', array_map('strval', $m->getReferences()->all()))),
            'de_email'    => $de instanceof Address ? $de->mail : '',
            'de_nombre'   => $de instanceof Address ? ($this->texto($de->personal) ?? null) : null,
            'para'        => $this->direcciones($m->getTo()->all()),
            'cc'          => $this->direcciones($m->getCc()->all()),
            'responder_a' => $this->direcciones($m->getReplyTo()->all()),
            'asunto'      => $this->texto((string) $m->getSubject()->first()),
            'fecha'       => $fecha instanceof CarbonInterface ? Carbon::instance($fecha)->utc() : now(),
            'texto'       => $m->hasTextBody() ? $m->getTextBody() : null,
            'html'        => $m->hasHTMLBody() ? $m->getHTMLBody() : null,
            'adjuntos'    => $m->getAttachments()->map(fn (Attachment $a) => [
                'nombre'    => $this->texto($a->getName() ?: $a->getFilename()) ?? 'adjunto',
                'tipo'      => $a->getMimeType() ?: $a->getContentType(),
                'tamano'    => (int) ($a->getSize() ?: strlen((string) $a->getContent())),
                'en_linea'  => strtolower((string) $a->getDisposition()) === 'inline',
                'contenido' => ($a->getSize() ?: strlen((string) $a->getContent())) <= $maxAdjunto ? (string) $a->getContent() : null,
            ])->values()->all(),
        ];
    }

    /**
     * UID => leído (\Seen) en el buzón. Abre la carpeta con EXAMINE (solo lectura):
     * así lo leído en el webmail también sale como leído en el panel.
     */
    public function leidos(string $ruta, array $uids): array
    {
        $carpeta = $this->carpeta($ruta);
        $carpeta->examine();
        $leidos = [];
        foreach (array_chunk($uids, 500) as $lote) {
            foreach ($this->cliente->getConnection()->flags($lote, IMAP::ST_UID)->validatedData() as $uid => $flags) {
                $leidos[(int) $uid] = in_array('\\Seen', (array) $flags, true);
            }
        }

        return $leidos;
    }

    /** Marca o desmarca un mensaje como leído en el buzón (lo que se hace en el panel se ve en el webmail). */
    public function marcarLeido(string $ruta, int $uid, bool $leido): void
    {
        $this->carpeta($ruta)->select();
        $this->cliente->getConnection()->store(['\\Seen'], $uid, null, $leido ? '+' : '-', true, IMAP::ST_UID);
    }

    /** La copia de lo enviado desde el panel, ya marcada como leída. */
    public function guardarEnviado(string $ruta, string $mensajeMime): void
    {
        $this->carpeta($ruta)->appendMessage($mensajeMime, ['\\Seen'], now());
    }

    private function carpeta(string $ruta): Folder
    {
        return $this->carpetas[$ruta] ??= $this->cliente->getFolderByPath($ruta)
            ?? throw new \RuntimeException("La carpeta {$ruta} no existe en el buzón.");
    }

    private function direcciones(array $lista): array
    {
        return collect($lista)->filter(fn ($a) => $a instanceof Address && $a->mail)
            ->map(fn (Address $a) => ['email' => $a->mail, 'nombre' => $this->texto($a->personal)])
            ->values()->all();
    }

    private function texto(mixed $valor): ?string
    {
        return self::cabecera((string) $valor);
    }

    /**
     * Cabecera legible: descodifica las palabras MIME (=?UTF-8?B?…?=, a veces en varios trozos
     * que la librería no une) y quita las comillas de los nombres («"Instagram"»).
     */
    public static function cabecera(?string $valor): ?string
    {
        $valor = (string) $valor;
        if (str_contains($valor, '=?')) {
            $valor = iconv_mime_decode($valor, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') ?: $valor;
        }
        $valor = trim($valor);
        if (strlen($valor) >= 2 && $valor[0] === '"' && str_ends_with($valor, '"')) {
            $valor = trim(substr($valor, 1, -1));
        }

        return $valor === '' ? null : $valor;
    }
}
