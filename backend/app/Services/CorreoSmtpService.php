<?php

namespace App\Services;

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

/** Envío por el SMTP del buzón (Hostinger, o Mailpit en local). Devuelve el mensaje MIME tal como salió. */
class CorreoSmtpService
{
    public function enviar(array $buzon, Email $email): string
    {
        $smtp = $buzon['smtp'];
        $transporte = new EsmtpTransport($smtp['host'], $smtp['puerto'], $smtp['tls']);
        if ($smtp['tls']) {
            $transporte->setUsername($buzon['usuario']);
            $transporte->setPassword((string) $buzon['password']);
        } else {
            $transporte->setAutoTls(false);
        }

        return $transporte->send($email)->toString();
    }
}
