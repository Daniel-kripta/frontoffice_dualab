<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Alta en el panel: aviso de la cuenta y enlace para elegir su propia contraseña (al usarlo, queda activada). */
class InvitacionGestionWeb extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $token, private string $rol) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('cms.url_publica').'/gestion-web/crear-contrasena?'.http_build_query([
            'token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset(), 'modo' => 'invitacion',
        ]);

        return (new MailMessage)
            ->subject('Tu cuenta en la gestión de la web de DuaLab')
            ->greeting('Hola, '.$notifiable->name)
            ->line('Se ha creado tu cuenta en el panel de gestión de dualab.es con el rol «'.(config('cms.nombres_roles')[$this->rol] ?? $this->rol).'».')
            ->line('Elige tu propia contraseña con este enlace; al hacerlo, tu cuenta queda activada y podrás entrar.')
            ->action('Elegir mi contraseña', $url)
            ->line('El enlace caduca en 3 días. Si no esperabas este correo, ignóralo.');
    }
}
