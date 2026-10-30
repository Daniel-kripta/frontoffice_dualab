<?php

namespace App\Services;

use App\Jobs\EnviarContactoAlCrm;
use App\Mail\ContactoAvisoInternoMail;
use App\Mail\ContactoConfirmacionMail;
use App\Models\Contacto;
use App\Support\ContactoCrm;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ContactoService
{
    /**
     * Guarda la solicitud y encola los dos correos (confirmación + aviso interno)
     * y, en los formularios por agente, su envío al CRM.
     * Todo va por cola: si el SMTP o el CRM tardan o fallan, la petición no
     * espera y los envíos se reintentan.
     */
    public function registrar(array $comunes, ?array $datos): Contacto
    {
        $this->limitarPorEmail($comunes['email']);

        $contacto = Contacto::create([...$comunes, 'datos' => $datos]);

        Mail::to($contacto->email)->queue(new ContactoConfirmacionMail($contacto));

        if ($destino = config('contacto.avisos_a')) {
            Mail::to($destino)->queue(new ContactoAvisoInternoMail($contacto));
        }

        if (ContactoCrm::aplica($contacto)) {
            EnviarContactoAlCrm::dispatch($contacto);
        }

        return $contacto;
    }

    /** Máximo de solicitudes por email y hora (además del throttle por IP de la ruta). */
    private function limitarPorEmail(string $email): void
    {
        $clave = 'contacto-email:'.sha1(Str::lower(trim($email)));

        if (RateLimiter::tooManyAttempts($clave, config('contacto.max_por_email_hora'))) {
            throw new ThrottleRequestsException('Has enviado demasiadas solicitudes. Inténtalo de nuevo más tarde.');
        }

        RateLimiter::hit($clave, 3600);
    }
}
