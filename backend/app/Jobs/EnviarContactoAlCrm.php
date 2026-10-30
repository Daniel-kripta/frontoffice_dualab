<?php

namespace App\Jobs;

use App\Models\Contacto;
use App\Support\ContactoCrm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Envía una solicitud de contacto al CRM (EspoCRM), donde se convierte en un
 * posible cliente. Va por cola, como los correos: la persona no espera al CRM
 * y, si el CRM no responde, se reintenta.
 *
 * El resultado queda en la solicitud: `crm_enviado_at` si llegó, `crm_error`
 * si no. Las que no llegaron se reenvían con `php artisan contactos:reenviar-crm`.
 */
class EnviarContactoAlCrm implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** Segundos entre reintentos cuando el CRM no está disponible. */
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public Contacto $contacto) {}

    public function handle(): void
    {
        $url = config('crm.url');
        $clave = config('crm.claves.'.$this->contacto->tipo);

        // Integración apagada, tipo sin punto de entrada en el CRM o ya enviada.
        if (! $url || ! $clave || ! ContactoCrm::aplica($this->contacto) || $this->contacto->crm_enviado_at) {
            return;
        }

        // Si el CRM está caído o no hay red, la excepción hace que la cola lo reintente.
        $respuesta = Http::timeout(config('crm.timeout'))
            ->acceptJson()
            ->post("{$url}/api/v1/LeadCapture/{$clave}", ContactoCrm::payload($this->contacto));

        if ($respuesta->successful()) {
            $this->contacto->forceFill(['crm_enviado_at' => now(), 'crm_error' => null])->save();

            return;
        }

        // 400: el CRM no acepta los datos (falta un obligatorio, teléfono no
        // válido, un valor de lista que no conoce...). Reintentar no sirve:
        // se deja constancia y la tarea queda como fallida.
        if ($respuesta->status() === 400) {
            $this->fail(new RequestException($respuesta));

            return;
        }

        $respuesta->throw(); // 404 (clave incorrecta) o 5xx: reintentar.
    }

    public function failed(?Throwable $e): void
    {
        $detalle = $e instanceof RequestException
            ? $e->response->status().' '.mb_substr($e->response->body(), 0, 500)
            : mb_substr((string) $e?->getMessage(), 0, 500);

        $this->contacto->forceFill(['crm_error' => $detalle])->save();
    }
}
