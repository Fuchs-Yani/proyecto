<?php

namespace App\Filament\Resources\AttachmentResource\Pages;

use App\Filament\Resources\AttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttachment extends EditRecord
{
    // Vincula la página de edición con AttachmentResource
    protected static string $resource = AttachmentResource::class;

    // Agrega el botón de "Eliminar" en la cabecera
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}