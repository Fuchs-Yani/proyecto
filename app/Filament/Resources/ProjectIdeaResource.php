<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectIdeaResource\Pages;
use App\Models\ProjectIdea;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;

class ProjectIdeaResource extends Resource
{
    protected static ?string $model = ProjectIdea::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('organizer_id')
                    ->relationship('organizer', 'name')
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('organizer.name')->label('Organizer')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                EditAction::make(),
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