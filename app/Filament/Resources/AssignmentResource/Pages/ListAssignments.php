<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssignments extends ListRecords
{
    // Conecta esta página de listado con la definición de AssignmentResource
    protected static string $resource = AssignmentResource::class;

    // Habilita el botón "Crear" en el encabezado superior
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}