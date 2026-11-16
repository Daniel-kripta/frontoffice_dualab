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

    public function __construct(public Contacto $contacto) {}

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
        $url = Ajuste::leer('integraciones.webhook_url');
        if (! $url || ! Ajuste::leer('integraciones.webhook_activo')) {
            return;
        }

        $cuerpo  = json_encode(self::payload($this->contacto), JSON_UNESCAPED_UNICODE);
        $secreto = Ajuste::leer('integraciones.webhook_secreto');

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
