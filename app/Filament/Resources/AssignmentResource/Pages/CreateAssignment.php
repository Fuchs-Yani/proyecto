<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignment extends CreateRecord
{
    // Conecta el formulario de creación con AssignmentResource
    protected static string $resource = AssignmentResource::class;
}