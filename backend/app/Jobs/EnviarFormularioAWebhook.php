<?php

namespace App\Jobs;

use App\Models\Ajuste;
use App\Models\Contacto;
use App\Services\BandejaFormulariosService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/**
 * Integraciones de la bandeja de formularios: reenvía cada envío al webhook
 * configurado en el panel (Zapier, Make, n8n, HubSpot…), que lo pasa al CRM o
 * a la herramienta de mail marketing. Va por cola: nunca bloquea al visitante.
 * Si hay secreto, firma el cuerpo con HMAC-SHA256 (cabecera X-DuaLab-Firma).
 */
class EnviarFormularioAWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    // 'webhook': la app DuaLab (captaciones en Central), configurada en el servidor (services.app_dualab).
    // 'crm': CRM externo opcional, configurado en el panel (integraciones.crm_*).
    public const DESTINOS = ['webhook', 'crm'];

    public function __construct(public Contacto $contacto, public string $destino = 'webhook') {}

    /**
     * Dirección y clave de un destino, o null si no está configurado (o, el CRM, desactivado).
     * La app: del .env; mientras no esté, lo que se guardó en el panel cuando se configuraba allí.
     *
     * @return array{url: string, secreto: ?string}|null
     */
    public static function destino(string $destino, bool $soloActivo = true): ?array
    {
        if ($destino === 'webhook') {
            $url = config('services.app_dualab.webhook_url');
            $secreto = config('services.app_dualab.webhook_secreto');
            if (! $url && Ajuste::leer('integraciones.webhook_activo')) {
                [$url, $secreto] = [Ajuste::leer('integraciones.webhook_url'), Ajuste::leer('integraciones.webhook_secreto')];
            }

            return $url ? ['url' => $url, 'secreto' => $secreto ?: null] : null;
        }
        if ($destino === 'crm') {
            $url = Ajuste::leer('integraciones.crm_url');
            if (! $url || ($soloActivo && ! Ajuste::leer('integraciones.crm_activo'))) {
                return null;
            }

            return ['url' => $url, 'secreto' => Ajuste::leer('integraciones.crm_secreto')];
        }

        return null;
    }

    /** Destinos a los que avisar: a cada uno se le manda su propio aviso (y sus reintentos). */
    public static function destinosActivos(): array
    {
        return array_values(array_filter(self::DESTINOS, fn ($d) => self::destino($d) !== null));
    }

    public static function payload(Contacto $c): array
    {
        return [
            'evento'     => 'formulario.recibido',
            'id'         => $c->id,
            'fecha'      => $c->created_at?->toIso8601String(),
            'formulario' => BandejaFormulariosService::formularioDe($c),
            'accion'     => $c->accion,
            'pagina'     => $c->pagina_origen,
            'contacto'   => [
                'nombre'    => $c->nombre,
                'apellidos' => $c->apellidos,
                'email'     => $c->email,
                'telefono'  => $c->telefono,
                'cargo'     => $c->cargo,
            ],
            'datos'      => $c->datos ?? (object) [],
        ];
    }

    public function handle(): void
    {
        $destino = self::destino($this->destino);
        if (! $destino) {
            return;
        }
        ['url' => $url, 'secreto' => $secreto] = $destino;

        $cuerpo = json_encode(self::payload($this->contacto), JSON_UNESCAPED_UNICODE);

        Http::timeout(10)
            ->withHeaders(array_filter([
                'Content-Type'   => 'application/json',
                'X-DuaLab-Firma' => $secreto ? hash_hmac('sha256', $cuerpo, $secreto) : null,
            ]))
            ->withBody($cuerpo, 'application/json')
            ->post($url)
            ->throw();
    }
}
