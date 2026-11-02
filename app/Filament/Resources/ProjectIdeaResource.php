<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectIdeaResource\Pages;
use App\Models\ProjectIdea;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectIdeaResource extends Resource
{
    protected static ?string $model = ProjectIdea::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'title';

    // El beneficiario solo gestiona sus propias ideas
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('beneficiario')) {
            $query->where('organizer_id', auth()->id());
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('organizer_id')
                    ->relationship('organizer', 'name')
                    ->default(fn () => auth()->id())
                    ->disabled(fn (string $operation) => $operation === 'create' && ! auth()->user()?->hasRole('admin'))
                    ->dehydrated()
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'available' => 'Available',
                        'in_development' => 'In Development',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('available')
                    ->required(),
                Forms\Components\TextInput::make('max_students')
                    ->label('Máximo de postulantes')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->nullable()
                    ->helperText('Dejar vacío para permitir postulaciones sin límite.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('organizer.name')->label('Organizer')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('applicants')
                    ->label('Postulantes / cupo')
                    ->state(fn (ProjectIdea $record): string => $record->max_students === null
                        ? $record->activeAssignmentsCount().' / sin límite'
                        : $record->activeAssignmentsCount().' / '.$record->max_students),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectIdeas::route('/'),
            'create' => Pages\CreateProjectIdea::route('/create'),
            'edit' => Pages\EditProjectIdea::route('/{record}/edit'),
        ];
    }
}
