<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda archivos en el disco "public" (storage/app/public, servido en /storage)
 * y devuelve su URL pública. Requiere `php artisan storage:link`.
 */
class ArchivosPublicos
{
    public static function guardar(UploadedFile $archivo, string $carpeta, ?string $urlAnterior = null): string
    {
        $ruta = $archivo->storeAs($carpeta, Str::uuid().'.'.$archivo->extension(), 'public');

        if ($urlAnterior) {
            self::eliminar($urlAnterior);
        }

        return Storage::disk('public')->url($ruta);
    }

    /** Elimina un archivo a partir de su URL si pertenece al disco público */
    public static function eliminar(string $url): void
    {
        $base = rtrim(Storage::disk('public')->url(''), '/').'/';

        if (str_starts_with($url, $base)) {
            Storage::disk('public')->delete(substr($url, strlen($base)));
        }
    }
}
