{{-- Correo de verificación de la cuenta. Variables: $url, $nombre, $minutos --}}
@extends('emails.layout')

@php($textoBoton = 'Verificar mi correo')

@section('titulo', 'Verifica tu cuenta')
@section('preheader', 'Confirma tu correo para empezar a reportar incidencias en Buenaventura.')
@section('etiqueta', 'Bienvenida')
@section('encabezado', '¡Te damos la bienvenida a la comunidad!')

@section('contenido')
  <p style="margin:0 0 16px 0;">Hola{{ $nombre ? ', '.$nombre : '' }}:</p>
  <p style="margin:0 0 16px 0;">
    Gracias por unirte a <strong style="color:#072e57;">Buenaventura Reporta</strong>. Solo falta un paso:
    confirma que este correo es tuyo para activar tu cuenta.
  </p>
  <p style="margin:0;">Con tu cuenta podrás:</p>
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:12px 0 0 0;">
    <tr>
      <td valign="top" style="padding:0 10px 8px 0; font-size:16px; line-height:24px; color:#398656; font-weight:800;">&#10003;</td>
      <td style="padding:0 0 8px 0; font-size:15px; line-height:24px; color:#334155;">Reportar problemas de tu barrio con foto y ubicación.</td>
    </tr>
    <tr>
      <td valign="top" style="padding:0 10px 8px 0; font-size:16px; line-height:24px; color:#398656; font-weight:800;">&#10003;</td>
      <td style="padding:0 0 8px 0; font-size:15px; line-height:24px; color:#334155;">Seguir su estado en tiempo real y conversar con la entidad responsable.</td>
    </tr>
    <tr>
      <td valign="top" style="padding:0 10px 0 0; font-size:16px; line-height:24px; color:#398656; font-weight:800;">&#10003;</td>
      <td style="padding:0; font-size:15px; line-height:24px; color:#334155;">Apoyar los reportes de otros ciudadanos y ganar insignias.</td>
    </tr>
  </table>
@endsection

@section('nota')
  Si no creaste una cuenta en Buenaventura Reporta, puedes ignorar este mensaje: sin la verificación, la cuenta no se activa.
@endsection
