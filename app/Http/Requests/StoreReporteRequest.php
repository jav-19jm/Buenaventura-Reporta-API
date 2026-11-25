<?php

namespace App\Http\Requests;

use App\Enums\PrioridadReporte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReporteRequest extends FormRequest
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
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:5000'],
            'categoria' => ['required', 'string', Rule::exists('categorias_reportes', 'nombre')->where('esta_activa', true)],
            'direccion_ubicacion' => ['nullable', 'string', 'max:255'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'prioridad' => ['nullable', Rule::enum(PrioridadReporte::class)],
            'id_entidad' => ['nullable', 'uuid', Rule::exists('entidades', 'id')->where('esta_activa', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria.exists' => 'La categoría seleccionada no está disponible.',
            'id_entidad.exists' => 'La entidad seleccionada no está disponible.',
        ];
    }
}
