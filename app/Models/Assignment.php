<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Assignment extends Model
{
    use HasFactory;

    public const STATUS_APPLIED = 'applied';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * Estados que ya no ocupan cupo en el proyecto.
     *
     * @var array<int, string>
     */
    public const INACTIVE_STATUSES = [self::STATUS_REJECTED, self::STATUS_WITHDRAWN];

    /**
     * Estados que cuentan como postulación aceptada.
     *
     * @var array<int, string>
     */
    public const ACCEPTED_STATUSES = [self::STATUS_ACCEPTED, self::STATUS_IN_PROGRESS];

    /**
     * Estados desde los que el alumno puede abandonar el proyecto.
     *
     * @var array<int, string>
     */
    public const WITHDRAWABLE_STATUSES = [self::STATUS_ACCEPTED, self::STATUS_IN_PROGRESS];

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
     * Indica si la postulación ya fue aceptada por el profesor y el proyecto
     * está en desarrollo, que es cuando se habilitan los avances.
     */
    public function canReceiveUpdates(): bool
    {
        return in_array($this->status, self::ACCEPTED_STATUSES, true)
            && $this->projectIdea?->status === 'in_development';
    }

    /**
     * Acepta la postulación y, si con esta ya están todos los aceptados,
     * pasa el proyecto a "en desarrollo".
     *
     * Devuelve false cuando la postulación ya fue evaluada, el profesor
     * todavía no definió el cupo o el cupo de aceptados ya está completo.
     */
    public function accept(): bool
    {
        return DB::transaction(function (): bool {
            $assignment = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($assignment->status !== self::STATUS_APPLIED) {
                return false;
            }

            $idea = ProjectIdea::whereKey($assignment->project_idea_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($idea->max_students === null) {
                return false;
            }

            $accepted = $idea->assignments()
                ->whereIn('status', self::ACCEPTED_STATUSES)
                ->count();

            if ($accepted >= $idea->max_students) {
                return false;
            }

            $assignment->update([
                'status' => self::STATUS_ACCEPTED,
                'started_at' => $assignment->started_at ?? now(),
            ]);

            $idea->syncDevelopmentStatus();

            return true;
        });
    }

    /**
     * Rechaza la postulación.
     */
    public function reject(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    /**
     * Abandona un proyecto en el que la postulación ya fue aceptada.
     *
     * La postulación queda como "withdrawn" (no se borra el historial), libera
     * el cupo y, si el proyecto estaba en desarrollo, se recalcula su estado.
     *
     * Devuelve false si la postulación no está en un estado que permita
     * abandonar el proyecto.
     */
    public function withdraw(): bool
    {
        return DB::transaction(function (): bool {
            $assignment = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($assignment->status, self::WITHDRAWABLE_STATUSES, true)) {
                return false;
            }

            $idea = ProjectIdea::whereKey($assignment->project_idea_id)
                ->lockForUpdate()
                ->firstOrFail();

            $assignment->update(['status' => self::STATUS_WITHDRAWN]);

            $idea->syncDevelopmentStatus();

            return true;
        });
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
