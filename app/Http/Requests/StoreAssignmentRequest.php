<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'student']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // viene por URL, pero lo validamos si viene en body también
            'project_idea_id' => ['sometimes', 'exists:project_ideas,id'],
        ];
    }
}
