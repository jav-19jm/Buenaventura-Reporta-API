<?php

namespace App\Http\Requests\Admin;

use App\Enums\TipoEntidad;
use App\Models\Entidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de entidades. Al crear se exige el correo y la contraseña
 * de la cuenta institucional que la gestionará.
 */
class EntidadRequest extends FormRequest
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
        /** @var Entidad|null $entidad */
        $entidad = $this->route('entidad');
        $creando = $entidad === null;
        $cuentaId = $entidad?->usuarios()->oldest('fecha_creacion')->value('id');

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('entidades', 'slug')->ignore($entidad?->id)],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'tipo' => ['required', Rule::enum(TipoEntidad::class)],
            'email' => [$creando ? 'required' : 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($cuentaId)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'color' => ['nullable', 'string', 'max:20'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
            'esta_activa' => ['sometimes', 'boolean'],
            'password' => [$creando ? 'required' : 'nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.alpha_dash' => 'El identificador solo puede tener letras, números y guiones.',
            'slug.unique' => 'Ya existe una entidad con ese identificador.',
            'email.unique' => 'Ese correo ya pertenece a otra cuenta.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Los formularios envían cadenas vacías para los campos opcionales
        $this->merge(collect($this->only(['descripcion', 'telefono', 'sitio_web', 'password', 'email']))
            ->map(fn ($valor) => $valor === '' ? null : $valor)
            ->all());
    }
}
