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

    protected static ?string $navigationLabel = 'Ideas y proyectos';

    protected static ?string $modelLabel = 'idea/proyecto';

    protected static ?string $pluralModelLabel = 'ideas y proyectos';

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
                    ->label('Beneficiario')
                    ->relationship('organizer', 'name')
                    ->default(fn () => auth()->id())
                    ->disabled(fn (string $operation) => $operation === 'create' && ! auth()->user()?->hasRole('admin'))
                    ->dehydrated()
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255)
                    ->extraInputAttributes(['spellcheck' => 'false']),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->required()
                    ->extraInputAttributes(['spellcheck' => 'false'])
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponible',
                        'in_development' => 'En desarrollo',
                        'completed' => 'Terminado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('available')
                    ->required(),
                Forms\Components\Select::make('tags')
                    ->label('Etiquetas')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la etiqueta')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->maxItems(ProjectIdea::MAX_TAGS)
                    ->helperText('Elegí hasta '.ProjectIdea::MAX_TAGS.' etiquetas para que la idea sea fácil de encontrar.')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('max_students')
                    ->label('Cantidad de alumnos')
                    ->numeric()
                    ->integer()
                    ->minValue(ProjectIdea::MIN_STUDENTS)
                    ->maxValue(ProjectIdea::MAX_STUDENTS)
                    ->nullable()
                    ->helperText('El profesor define entre '.ProjectIdea::MIN_STUDENTS.' y '.ProjectIdea::MAX_STUDENTS.' alumnos. Mientras esté vacío no se aceptan postulaciones.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('organizer.name')->label('Beneficiario')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                        'available' => 'Disponible',
                        'in_development' => 'En desarrollo',
                        'completed' => 'Terminado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Etiquetas')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                Tables\Columns\TextColumn::make('applicants')
                    ->label('Postulantes / cupo')
                    ->state(fn (ProjectIdea $record): string => $record->max_students === null
                        ? $record->activeAssignmentsCount().' / pendiente'
                        : $record->activeAssignmentsCount().' / '.$record->max_students),
                Tables\Columns\TextColumn::make('created_at')->label('Creada el')->dateTime('d/m/Y H:i')->sortable(),
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
