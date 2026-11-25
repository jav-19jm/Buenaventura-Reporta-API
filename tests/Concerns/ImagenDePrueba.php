<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;

/**
 * Imagen PNG real de 1x1 px. Evita depender de la extensión GD que
 * necesita UploadedFile::fake()->image().
 */
trait ImagenDePrueba
{
    protected function imagenPng(string $nombre = 'imagen.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($nombre, $png);
    }
}
