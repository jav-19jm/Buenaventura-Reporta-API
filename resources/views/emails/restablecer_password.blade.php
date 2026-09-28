<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restablecer contraseña - Buenaventura Reporta</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
    <!-- Header con Logo -->
    <tr>
      <td align="center" style="padding: 40px 0 20px 0; background: linear-gradient(135deg, #fffbeb 0%, #f0fdf4 100%);">
        <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #eab308 0%, #16a34a 100%); border-radius: 16px; display: inline-block; vertical-align: middle; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-top: 16px;">
            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
        </div>
        <h1 style="margin: 16px 0 0 0; color: #0f172a; font-size: 24px; font-weight: 700;">Buenaventura Reporta</h1>
      </td>
    </tr>
    <!-- Contenido -->
    <tr>
      <td style="padding: 40px;">
        <h2 style="margin: 0 0 16px 0; color: #1e293b; font-size: 20px; font-weight: 600; text-align: center;">¿Olvidaste tu contraseña?</h2>
        <p style="margin: 0 0 24px 0; color: #475569; font-size: 16px; line-height: 1.6; text-align: center;">
          Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. No te preocupes, puedes volver a entrar haciendo clic en el siguiente botón:
        </p>
        <!-- Botón CTA -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td align="center">
              <a href="{{ .ConfirmationURL }}" style="display: inline-block; padding: 16px 32px; background: linear-gradient(to right, #16a34a, #15803d); color: #ffffff; text-decoration: none; font-weight: 600; border-radius: 12px; font-size: 16px; box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.2);">
                Restablecer contraseña
              </a>
            </td>
          </tr>
        </table>
        <p style="margin: 32px 0 0 0; color: #64748b; font-size: 14px; text-align: center; line-height: 1.5;">
          Por seguridad, este enlace expirará pronto. Si tú no solicitaste este cambio, simplemente ignora este correo y tu contraseña seguirá siendo la misma.
        </p>
      </td>
    </tr>
    <!-- Footer -->
    <tr>
      <td style="padding: 24px; background-color: #f1f5f9; text-align: center;">
        <p style="margin: 0; color: #64748b; font-size: 12px;">
          Buenaventura Reporta - Plataforma de Participación Ciudadana
        </p>
      </td>
    </tr>
  </table>
</body>
</html>
