<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** «¿Has olvidado tu contraseña?» del panel de gestión web. */
class RestablecerContrasenaGestionWeb extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('cms.url_publica').'/gestion-web/crear-contrasena?'.http_build_query([
            'token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset(), 'modo' => 'restablecer',
        ]);

        return (new MailMessage)
            ->subject('Restablecer tu contraseña de la gestión web de DuaLab')
            ->line('Hemos recibido una solicitud para cambiar la contraseña de tu cuenta del panel de gestión de dualab.es.')
            ->action('Elegir una contraseña nueva', $url)
            ->line('El enlace caduca en 60 minutos. Si no lo has pedido tú, ignora este correo: tu contraseña no cambia.');
    }
}
