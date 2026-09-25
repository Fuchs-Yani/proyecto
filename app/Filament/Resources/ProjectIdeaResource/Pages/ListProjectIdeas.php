<?php

namespace App\Filament\Resources\ProjectIdeaResource\Pages;

use App\Filament\Resources\ProjectIdeaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectIdeas extends ListRecords
{
    protected static string $resource = ProjectIdeaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}