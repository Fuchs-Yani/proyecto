<?php

namespace App\Filament\Resources\AttachmentResource\Pages;

use App\Filament\Resources\AttachmentResource;
use Filament\Resources\Pages\CreateRecord;

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
}
