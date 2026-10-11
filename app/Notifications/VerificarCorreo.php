<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de confirmación de cuenta con la plantilla propia de la plataforma.
 * El enlace es una URL firmada del backend (ruta verification.verify) que,
 * al abrirse, marca el correo como verificado y redirige al frontend.
 *
 * Se envía en segundo plano (cola de Redis) para no hacer esperar el registro.
 */
class VerificarCorreo extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifica tu cuenta - Buenaventura Reporta')
            ->view('emails.confirmar_correo', ['url' => $this->verificationUrl($notifiable)]);
    }
}
