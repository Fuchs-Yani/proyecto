<?php

namespace App\Filament\Resources\AttachmentResource\Pages;

use App\Filament\Resources\AttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttachments extends ListRecords
{
    // Vincula esta página con el recurso principal AttachmentResource
    protected static string $resource = AttachmentResource::class;

    // Define los botones de la cabecera superior (ej: botón "Crear")
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}