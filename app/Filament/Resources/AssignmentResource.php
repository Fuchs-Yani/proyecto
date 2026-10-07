<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
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
        $transitions = [
            'applied' => ['applied', 'accepted', 'rejected'],
            'accepted' => ['accepted', 'in_progress', 'rejected'],
            'in_progress' => ['in_progress', 'completed'],
            'rejected' => ['rejected'],
            'completed' => ['completed'],
        ];

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
                    ->default(fn () => auth()->id())
                    ->disabled(fn (string $operation) => $operation === 'create' && ! auth()->user()?->hasRole('admin'))
                    ->dehydrated()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options(function (?Assignment $record) use ($transitions): array {
                        $all = [
                            'applied' => 'Postulado',
                            'accepted' => 'Aceptado',
                            'rejected' => 'Rechazado',
                            'in_progress' => 'En Progreso',
                            'completed' => 'Completado',
                        ];
                        if (! $record?->exists) {
                            return ['applied' => 'Postulado']; // al crear solo applied
                        }
                        $allowed = $transitions[$record->status] ?? [$record->status];

                        return array_intersect_key($all, array_flip($allowed));
                    })
                    ->default('applied')
                    ->required(),

                Forms\Components\DateTimePicker::make('applied_at')
                    ->label('Postulado el')
                    ->default(now())
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\DateTimePicker::make('started_at')
                    ->label('Iniciado el')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\DateTimePicker::make('finished_at')
                    ->label('Finalizado el')
                    ->disabled()
                    ->dehydrated(),
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
                Action::make('accept')
                    ->label('Aceptar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Assignment $record) => $record->status === 'applied')
                    ->action(fn (Assignment $record) => $record->update(['status' => 'accepted'])),
                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Assignment $record) => in_array($record->status, ['applied', 'accepted'], true))
                    ->action(fn (Assignment $record) => $record->update(['status' => 'rejected'])),
                Action::make('start')
                    ->label('Iniciar')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (Assignment $record) => $record->status === 'accepted')
                    ->action(fn (Assignment $record) => $record->update(['status' => 'in_progress', 'started_at' => now()])),
                Action::make('complete')
                    ->label('Completar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Assignment $record) => $record->status === 'in_progress')
                    ->action(fn (Assignment $record) => $record->update(['status' => 'completed', 'finished_at' => now()])),
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
