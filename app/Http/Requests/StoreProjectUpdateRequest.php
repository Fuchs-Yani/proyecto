<?php

namespace App\Http\Requests;

use App\Models\ProjectUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectUpdateRequest extends FormRequest
{
    /**
     * La autorización se resuelve en el controlador con la policy.
     */
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
            'stage' => ['required', Rule::in(array_keys(ProjectUpdate::STAGES))],
            'description' => ['required', 'string', 'max:5000'],
            'repository_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'images' => ['nullable', 'array', 'max:'.ProjectUpdate::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stage.required' => 'Elegí la etapa del proyecto a la que corresponde el avance.',
            'stage.in' => 'La etapa seleccionada no es válida.',
            'description.required' => 'Contanos qué avanzaste en el proyecto.',
            'description.max' => 'La descripción del avance no puede superar los 5000 caracteres.',
            'repository_url.url' => 'La URL del repositorio debe empezar con http:// o https://.',
            'repository_url.max' => 'La URL del repositorio es demasiado larga.',
            'images.array' => 'Las imágenes no se enviaron correctamente.',
            'images.max' => 'Podés adjuntar hasta '.ProjectUpdate::MAX_IMAGES.' imágenes por avance.',
            'images.*.image' => 'Uno de los archivos no es una imagen válida.',
            'images.*.mimes' => 'Solo se permiten imágenes JPG, PNG, WEBP o GIF.',
            'images.*.max' => 'Cada imagen no puede superar los 5 MB.',
            'images.*.uploaded' => 'No se pudo subir una de las imágenes. Verificá el tamaño permitido.',
        ];
    }
}
