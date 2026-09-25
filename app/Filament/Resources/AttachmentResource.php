<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttachmentResource\Pages;
use App\Models\Attachment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;

class AttachmentResource extends Resource
{
    // 1. Vinculación con la Base de Datos
    protected static ?string $model = Attachment::class;

    // 2. Icono e Identificación en la Barra Lateral
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-paper-clip';
    protected static ?string $recordTitleAttribute = 'file_name';

    // 3. Definición del Formulario (Crear y Editar)
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('project_idea_id')
                    ->relationship('projectIdea', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('file_name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\FileUpload::make('file_path')
                    ->directory('attachments')
                    ->required(),

                Forms\Components\TextInput::make('file_type')
                    ->placeholder('ej: pdf, png, zip')
                    ->required(),
            ]);
    }

    // 4. Definición de la Tabla (Listado principal)
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('projectIdea.title')
                    ->label('Proyecto Asociado')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_type')
                    ->badge(),

                Tables\Columns\TextColumn::make('uploaded_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    // 5. Mapeo de Rutas y Páginas
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttachments::route('/'),
            'create' => Pages\CreateAttachment::route('/create'),
            'edit' => Pages\EditAttachment::route('/{record}/edit'),
        ];
    }
}