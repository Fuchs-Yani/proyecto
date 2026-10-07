<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssignmentResource extends Resource
{
    // 1. Modelo de Base de Datos
    protected static ?string $model = Assignment::class;

    // 2. Icono del Menú Lateral (icono de usuario/check o lista de tareas)
   protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    // 3. Configuración del Formulario (Crear y Editar)
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('project_idea_id')
                    ->relationship('projectIdea', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'applied' => 'Postulado',
                        'accepted' => 'Aceptado',
                        'rejected' => 'Rechazado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                    ])
                    ->default('applied')
                    ->required(),
            ]);
    }

    // 4. Configuración de la Tabla de Listado
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('projectIdea.title')
                    ->label('Proyecto')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Estudiante')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                   ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                        'applied' => 'Postulado',
                        'accepted' => 'Aceptado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'rejected' => 'Rechazado',
                        default => $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'applied' => 'gray',
                        'accepted' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('applied_at')
                    ->label('Fecha de Postulación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filtrar por Estado')
                    ->options([
                        'applied' => 'Postulado',
                        'accepted' => 'Aceptado',
                        'rejected' => 'Rechazado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                    ]),
             ])
            ->actions([
                EditAction::make(),
            ]);
    }

    // 5. Conexión con las Páginas
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }
}