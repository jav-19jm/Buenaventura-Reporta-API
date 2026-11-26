{{--
  Plantilla base de los correos de Buenaventura Reporta.

  Diseño con tablas y estilos en línea para que se vea igual en Gmail, Outlook,
  Apple Mail y clientes móviles. Ancho máximo de 600 px; por debajo de 620 px
  el contenido ocupa todo el ancho y el botón se estira.

  Secciones que define cada correo:
    titulo, preheader, etiqueta, encabezado, contenido
  Variables opcionales: $url (botón), $textoBoton, $minutos (validez del enlace)

  El logo (PNG) lo sirve el frontend en /email/logo.png: los clientes de correo
  no muestran SVG.
--}}
@php
    $sitio = rtrim(config('app.frontend_url'), '/');
    $logo = $sitio.'/email/logo.png';
@endphp
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">
  <title>@yield('titulo') · Buenaventura Reporta</title>
  <!--[if mso]>
  <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
  <![endif]-->
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
    a { color: #0a5caa; }
    .boton:hover { background-color: #072e57 !important; }

    @media only screen and (max-width: 620px) {
      .contenedor { width: 100% !important; }
      .marco { padding: 0 !important; }
      .tarjeta { border-radius: 0 !important; }
      .relleno { padding-left: 24px !important; padding-right: 24px !important; }
      .encabezado { font-size: 22px !important; line-height: 30px !important; }
      .boton-tabla { width: 100% !important; }
      .boton { display: block !important; }
    }
  </style>
</head>
<body style="margin:0; padding:0; width:100%; background-color:#eef3f9; font-family:'Nunito','Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

  {{-- Texto de vista previa (aparece junto al asunto en la bandeja de entrada) --}}
  <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#eef3f9; opacity:0;">
    @yield('preheader')&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef3f9;">
    <tr>
      <td class="marco" align="center" style="padding:32px 12px;">

        <!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
        <table role="presentation" class="contenedor" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px;">

          <tr>
            <td class="tarjeta" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 8px 24px rgba(7,46,87,0.08);">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">

                {{-- Logo --}}
                <tr>
                  <td align="center" style="padding:32px 24px 24px 24px; background-color:#ffffff;">
                    <a href="{{ $sitio }}" target="_blank" style="text-decoration:none;">
                      <img src="{{ $logo }}" width="220" alt="Buenaventura Reporta" style="display:block; width:220px; max-width:100%; height:auto; font-family:'Nunito',Arial,sans-serif; font-size:22px; font-weight:800; color:#0a5caa;">
                    </a>
                  </td>
                </tr>

                {{-- Franja con los colores de la marca --}}
                <tr>
                  <td style="padding:0; font-size:0; line-height:0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td width="50%" height="5" style="background-color:#0a5caa; font-size:0; line-height:0;">&nbsp;</td>
                        <td width="30%" height="5" style="background-color:#398656; font-size:0; line-height:0;">&nbsp;</td>
                        <td width="20%" height="5" style="background-color:#f8b71d; font-size:0; line-height:0;">&nbsp;</td>
                      </tr>
                    </table>
                  </td>
                </tr>

                {{-- Cuerpo --}}
                <tr>
                  <td class="relleno" style="padding:40px 48px 8px 48px;">
                    <p style="margin:0 0 8px 0; font-size:12px; line-height:16px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; color:#137cc8;">
                      @yield('etiqueta')
                    </p>
                    <h1 class="encabezado" style="margin:0 0 20px 0; font-size:26px; line-height:34px; font-weight:800; color:#072e57;">
                      @yield('encabezado')
                    </h1>
                    <div style="font-size:16px; line-height:26px; color:#334155;">
                      @yield('contenido')
                    </div>
                  </td>
                </tr>

                @isset($url)
                  {{-- Botón principal (compatible con Outlook) --}}
                  <tr>
                    <td class="relleno" align="center" style="padding:24px 48px 8px 48px;">
                      <table role="presentation" class="boton-tabla" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                          <td align="center" bgcolor="#0a5caa" style="border-radius:10px; background-color:#0a5caa;">
                            <!--[if mso]>
                            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $url }}" style="height:52px; v-text-anchor:middle; width:280px;" arcsize="20%" stroke="f" fillcolor="#0a5caa">
                              <center style="color:#ffffff; font-family:Arial,sans-serif; font-size:16px; font-weight:bold;">{{ $textoBoton }}</center>
                            </v:roundrect>
                            <![endif]-->
                            <!--[if !mso]><!-->
                            <a href="{{ $url }}" target="_blank" class="boton" style="display:inline-block; padding:16px 36px; font-family:'Nunito','Segoe UI',Arial,sans-serif; font-size:16px; line-height:20px; font-weight:800; color:#ffffff; text-decoration:none; border-radius:10px; background-color:#0a5caa;">
                              {{ $textoBoton }}
                            </a>
                            <!--<![endif]-->
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                @endisset

                @isset($minutos)
                  {{-- Validez del enlace --}}
                  <tr>
                    <td class="relleno" style="padding:24px 48px 0 48px;">
                      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                          <td style="padding:14px 18px; background-color:#fff8e6; border-left:4px solid #f8b71d; border-radius:8px; font-size:14px; line-height:21px; color:#5b4a1a;">
                            &#9201;&nbsp; Por seguridad, este enlace vence en <strong>{{ $minutos }} minutos</strong>.
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                @endisset

                @isset($url)
                  {{-- Enlace alternativo --}}
                  <tr>
                    <td class="relleno" style="padding:24px 48px 0 48px;">
                      <p style="margin:0 0 6px 0; font-size:13px; line-height:20px; color:#64748b;">
                        ¿El botón no funciona? Copia y pega este enlace en tu navegador:
                      </p>
                      <p style="margin:0; font-size:12px; line-height:18px; word-break:break-all;">
                        <a href="{{ $url }}" target="_blank" style="color:#0a5caa; text-decoration:underline;">{{ $url }}</a>
                      </p>
                    </td>
                  </tr>
                @endisset

                {{-- Nota final --}}
                <tr>
                  <td class="relleno" style="padding:28px 48px 40px 48px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="border-top:1px solid #e2e8f0; padding-top:20px; font-size:13px; line-height:20px; color:#64748b;">
                          @yield('nota')
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                {{-- Pie --}}
                <tr>
                  <td class="relleno" align="center" style="padding:28px 48px; background-color:#072e57;">
                    <p style="margin:0 0 6px 0; font-size:15px; line-height:22px; font-weight:800; color:#ffffff;">
                      Buenaventura Reporta
                    </p>
                    <p style="margin:0 0 14px 0; font-size:13px; line-height:20px; color:#b9cde3;">
                      Reporta, sigue y mejora tu ciudad. Juntos construimos una mejor Buenaventura.
                    </p>
                    <p style="margin:0 0 14px 0; font-size:13px; line-height:20px;">
                      <a href="{{ $sitio }}" target="_blank" style="color:#f8b71d; font-weight:700; text-decoration:none;">{{ preg_replace('#^https?://#', '', $sitio) }}</a>
                    </p>
                    <p style="margin:0; font-size:11px; line-height:17px; color:#8aa6c4;">
                      Proyecto ciudadano y académico, independiente de la Alcaldía de Buenaventura.<br>
                      Este es un correo automático; por favor no respondas a este mensaje.<br>
                      &copy; {{ date('Y') }} Buenaventura Reporta
                    </p>
                  </td>
                </tr>

              </table>
            </td>
          </tr>

        </table>
        <!--[if mso]></td></tr></table><![endif]-->

      </td>
    </tr>
  </table>
</body>
</html>
