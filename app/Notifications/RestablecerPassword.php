<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * Correo de recuperación de contraseña. El enlace apunta a la pantalla
 * /reset-password del frontend con el token y el correo como parámetros.
 * Se envía en segundo plano (cola de Redis).
 */
class RestablecerPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Restablecer contraseña - Buenaventura Reporta')
            ->view('emails.restablecer_password', [
                'url' => $this->frontendUrl($notifiable),
                'nombre' => Str::of($notifiable->nombre_completo ?? '')->trim()->before(' ')->toString(),
                'minutos' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
            ]);
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
