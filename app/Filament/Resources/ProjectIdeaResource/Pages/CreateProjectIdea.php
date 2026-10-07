<?php

namespace App\Filament\Resources\ProjectIdeaResource\Pages;

use App\Filament\Resources\ProjectIdeaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectIdea extends CreateRecord
{
    protected static string $resource = ProjectIdeaResource::class;

    // Auto-set organizer_id = auth()->id() salvo admin que puede elegir
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()?->hasRole('admin') || empty($data['organizer_id'])) {
            $data['organizer_id'] = auth()->id();
        }

        return $data;
    }
}
