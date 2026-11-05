<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $table = 'assignments';

    protected $fillable = [
        'project_idea_id',
        'student_id',
        'status',
        'applied_at',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function projectIdea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'project_idea_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class, 'assignment_id');
    }

    /**
     * Al eliminar la postulación se eliminan sus avances y las imágenes
     * mediante Eloquent para que se borren también los archivos del disco.
     */
    protected static function booted(): void
    {
        static::deleting(function (Assignment $assignment): void {
            $assignment->updates()->get()->each(function (ProjectUpdate $update): void {
                $update->delete();
            });
        });
    }

    /**
     * Filtra postulaciones por alumno (nombre o email) o por título del proyecto.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->whereHas('student', function (Builder $student) use ($term): void {
                $student->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%');
            })->orWhereHas(
                'projectIdea',
                fn (Builder $idea) => $idea->where('title', 'like', '%'.$term.'%'),
            );
        });
    }
}
