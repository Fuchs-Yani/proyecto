<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssignment extends EditRecord
{
    // Conecta el formulario de edición con AssignmentResource
    protected static string $resource = AssignmentResource::class;

    // Agrega el botón de "Eliminar" en la cabecera
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Auto-set started_at / finished_at según transición
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $original = $this->record->status;

        if ($original !== ($data['status'] ?? $original)) {
            if (($data['status'] ?? null) === 'in_progress' && empty($this->record->started_at)) {
                $data['started_at'] = now();
            }
            if (($data['status'] ?? null) === 'completed' && empty($this->record->finished_at)) {
                $data['finished_at'] = now();
                $data['started_at'] ??= $this->record->started_at ?? now();
            }
        }

        return $data;
    }
}
