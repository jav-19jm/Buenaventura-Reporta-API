<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoticiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'contenido' => ['required', 'string', 'max:20000'],
            'categoria' => ['nullable', 'string', 'max:50'],
            'url_imagen' => ['nullable', 'url', 'max:2048'],
            'esta_publicada' => ['sometimes', 'boolean'],
            'id_entidad' => ['nullable', 'uuid', Rule::exists('entidades', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect($this->only(['url_imagen', 'id_entidad', 'categoria']))
            ->map(fn ($valor) => $valor === '' ? null : $valor)
            ->all());
    }
}
