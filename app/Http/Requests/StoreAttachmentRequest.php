<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'beneficiario']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_idea_id' => ['sometimes', 'exists:project_ideas,id'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,gif', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Selecciona un archivo para subir.',
            'file.mimes' => 'Solo se permiten archivos PDF o imágenes (JPG, PNG, WEBP o GIF).',
            'file.max' => 'El archivo no puede superar los 10 MB.',
        ];
    }
}
