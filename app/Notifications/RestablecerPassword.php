<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de recuperación de contraseña. El enlace apunta a la pantalla
 * /reset-password del frontend con el token y el correo como parámetros.
 */
class RestablecerPassword extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Restablecer contraseña - Buenaventura Reporta')
            ->view('emails.restablecer_password', ['url' => $this->frontendUrl($notifiable)]);
    }

    public function frontendUrl($notifiable): string
    {
        $query = http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return rtrim(config('app.frontend_url'), '/').'/reset-password?'.$query;
    }
}
