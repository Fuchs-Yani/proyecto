<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttachmentResource\Pages;
use App\Models\Attachment;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AttachmentResource extends Resource
{
    // 1. Vinculación con la Base de Datos
    protected static ?string $model = Attachment::class;

    // 2. Icono e Identificación en la Barra Lateral
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationLabel = 'Adjuntos';

    protected static ?string $modelLabel = 'adjunto';

    protected static ?string $pluralModelLabel = 'adjuntos';

    protected static ?string $recordTitleAttribute = 'file_name';

    // 3. Definición del Formulario (Crear y Editar)
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('project_idea_id')
                    ->label('Proyecto')
                    ->relationship('projectIdea', 'title')
                    ->searchable()
                    ->preload()
                    ->helperText('Máximo '.ProjectIdea::MAX_ATTACHMENTS.' archivos por idea.')
                    ->required(),

                Forms\Components\TextInput::make('file_name')
                    ->label('Nombre del archivo')
                    ->required()
                    ->maxLength(255),

                Forms\Components\FileUpload::make('file_path')
                    ->label('Archivo')
                    ->disk('public')
                    ->directory('attachments')
                    ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg', 'image/webp', 'image/gif'])
                    ->maxSize(10240)
                    ->helperText('PDF o imágenes (JPG, PNG, WEBP, GIF) · máx. 10 MB')
                    ->downloadable()
                    ->required(),

                Forms\Components\TextInput::make('file_type')
                    ->label('Tipo de archivo')
                    ->placeholder('ej: pdf, png, jpg')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\DateTimePicker::make('uploaded_at')
                    ->label('Subido el')
                    ->default(now())
                    ->displayFormat('d/m/Y H:i')
                    ->seconds(false)
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    // 4. Definición de la Tabla (Listado principal)
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('Archivo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('projectIdea.title')
                    ->label('Proyecto Asociado')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_type')
                    ->label('Tipo')
                    ->badge(),

                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label('Subido el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Attachment $record) => Storage::disk('public')->url($record->file_path))
                    ->openUrlInNewTab(),
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
