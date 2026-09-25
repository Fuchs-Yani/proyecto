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
}