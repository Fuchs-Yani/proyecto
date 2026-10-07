<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignment extends CreateRecord
{
    // Conecta el formulario de creación con AssignmentResource
    protected static string $resource = AssignmentResource::class;

    // Auto-set applied_at = now() + student_id si no es admin
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()?->hasRole('admin') || empty($data['student_id'])) {
            if (! auth()->user()?->hasRole('admin')) {
                $data['student_id'] = auth()->id();
            }
        }
        $data['applied_at'] ??= now();
        $data['status'] ??= 'applied';

        return $data;
    }
}
