<?php

namespace App\Filament\Resources\AttachmentResource\Pages;

use App\Filament\Resources\AttachmentResource;
use App\Models\ProjectIdea;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateAttachment extends CreateRecord
{
    // Vincula la página de creación con AttachmentResource
    protected static string $resource = AttachmentResource::class;

    // Auto-set uploaded_at + file_type desde el archivo subido
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_at'] ??= now();

        if (empty($data['file_type']) && ! empty($data['file_path'])) {
            $data['file_type'] = strtolower(pathinfo(is_array($data['file_path']) ? ($data['file_path'][0] ?? '') : $data['file_path'], PATHINFO_EXTENSION));
        }
        if (empty($data['file_name']) && ! empty($data['file_path'])) {
            $path = is_array($data['file_path']) ? ($data['file_path'][0] ?? '') : $data['file_path'];
            $data['file_name'] = basename($path);
        }

        return $data;
    }

    /**
     * Impide crear más adjuntos que los permitidos por idea.
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var ProjectIdea|null $idea */
        $idea = ProjectIdea::find($data['project_idea_id'] ?? null);

        if ($idea?->hasReachedAttachmentLimit()) {
            throw ValidationException::withMessages([
                'data.project_idea_id' => 'Esta idea ya tiene el máximo de '.ProjectIdea::MAX_ATTACHMENTS.' archivos adjuntos.',
            ]);
        }

        return parent::handleRecordCreation($data);
    }
}
