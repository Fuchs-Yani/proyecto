<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'organization']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_idea_id' => ['sometimes', 'exists:project_ideas,id'],
            'file' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,zip', 'max:10240'],
        ];
    }
}
