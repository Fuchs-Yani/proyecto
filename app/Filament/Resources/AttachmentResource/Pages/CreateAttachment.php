<?php

namespace App\Filament\Resources\AttachmentResource\Pages;

use App\Filament\Resources\AttachmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttachment extends CreateRecord
{
    // Vincula la página de creación con AttachmentResource
    protected static string $resource = AttachmentResource::class;
}