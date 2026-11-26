{{-- Correo de recuperación de contraseña. Variables: $url, $nombre, $minutos --}}
@extends('emails.layout')

@php($textoBoton = 'Crear nueva contraseña')

@section('titulo', 'Restablece tu contraseña')
@section('preheader', 'Recibimos una solicitud para cambiar la contraseña de tu cuenta.')
@section('etiqueta', 'Seguridad de tu cuenta')
@section('encabezado', 'Restablece tu contraseña')

@section('contenido')
  <p style="margin:0 0 16px 0;">Hola{{ $nombre ? ', '.$nombre : '' }}:</p>
  <p style="margin:0;">
    Recibimos una solicitud para restablecer la contraseña de tu cuenta en
    <strong style="color:#072e57;">Buenaventura Reporta</strong>. Pulsa el botón para elegir una nueva.
  </p>
@endsection

@section('nota')
  <strong style="color:#072e57;">¿No fuiste tú?</strong> Ignora este correo: tu contraseña actual seguirá funcionando
  y nadie podrá cambiarla sin este enlace. Nunca te pediremos tu contraseña por correo.
@endsection
