<?php

/*
|--------------------------------------------------------------------------
| Mensajes de validación en español
|--------------------------------------------------------------------------
| Cubre las reglas que usa la API. Las que falten se muestran con el texto
| del idioma de respaldo (APP_FALLBACK_LOCALE).
*/

return [
    'accepted' => 'El campo :attribute debe ser aceptado.',
    'array' => 'El campo :attribute debe ser una lista.',
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'enum' => 'El valor seleccionado en :attribute no es válido.',
    'exists' => 'El valor seleccionado en :attribute no existe.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El valor seleccionado en :attribute no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'file' => 'El archivo :attribute no debe pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'file' => 'El archivo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un número.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser un texto.',
    'unique' => 'El valor de :attribute ya está en uso.',
    'uploaded' => 'No se pudo subir el archivo :attribute.',
    'uuid' => 'El campo :attribute debe ser un identificador válido.',

    'attributes' => [
        'avatar' => 'foto de perfil',
        'categoria' => 'categoría',
        'descripcion' => 'descripción',
        'direccion_ubicacion' => 'dirección',
        'email' => 'correo electrónico',
        'id_entidad' => 'entidad',
        'imagen' => 'imagen',
        'latitud' => 'latitud',
        'longitud' => 'longitud',
        'mensaje' => 'mensaje',
        'motivo' => 'motivo',
        'nombre_completo' => 'nombre completo',
        'password' => 'contraseña',
        'prioridad' => 'prioridad',
        'tipo_voto' => 'tipo de voto',
        'titulo' => 'título',
    ],
];
