<?php

namespace App\Filament\Resources\ProjectIdeaResource\Pages;

use App\Filament\Resources\ProjectIdeaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectIdea extends EditRecord
{
    protected static string $resource = ProjectIdeaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}