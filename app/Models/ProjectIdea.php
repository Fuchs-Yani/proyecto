<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ProjectIdea extends Model
{
    use HasFactory;

    /**
     * Cantidad máxima de archivos adjuntos permitidos por idea.
     */
    public const MAX_ATTACHMENTS = 2;

    /**
     * Cantidad máxima de etiquetas permitidas por idea.
     */
    public const MAX_TAGS = 3;

    /**
     * Cantidad mínima de alumnos que puede tener un proyecto.
     */
    public const MIN_STUDENTS = 1;

    /**
     * Cantidad máxima de alumnos que puede tener un proyecto.
     */
    public const MAX_STUDENTS = 4;

    protected $table = 'project_ideas';

    protected $fillable = [
        'organizer_id',
        'title',
        'description',
        'status',
        'max_students',
        'started_early',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'max_students' => 'integer',
            'started_early' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Indica si el profesor todavía no definió el cupo de alumnos.
     */
    public function isPendingReview(): bool
    {
        return $this->status === 'available' && $this->max_students === null;
    }

    /**
     * Indica si la idea todavía acepta postulaciones.
     * Hasta que el profesor no define el cupo, la idea no se puede postular.
     */
    public function isOpenForApplications(): bool
    {
        return $this->status === 'available' && $this->max_students !== null;
    }

    /**
     * Pasa la idea a "en desarrollo" cuando el cupo está completo y todos
     * los postulantes fueron aceptados. No modifica ideas ya cerradas.
     */
    public function markAsInDevelopment(): void
    {
        if ($this->status === 'available') {
            $this->update([
                'status' => 'in_development',
                'started_early' => false,
            ]);
        }
    }

    /**
     * Inicia el proyecto con los postulantes que ya fueron aceptados, aunque
     * el cupo máximo no esté completo. Las postulaciones pendientes se
     * rechazan para cerrar la convocatoria.
     *
     * Devuelve false si el proyecto no está disponible o no tiene al menos
     * un integrante aceptado.
     */
    public function startDevelopment(): bool
    {
        return DB::transaction(function (): bool {
            $idea = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($idea->status !== 'available' || $idea->acceptedAssignmentsCount() < 1) {
                return false;
            }

            $idea->assignments()
                ->where('status', Assignment::STATUS_APPLIED)
                ->update(['status' => Assignment::STATUS_REJECTED]);

            $idea->update([
                'status' => 'in_development',
                'started_early' => true,
            ]);

            return true;
        });
    }

    /**
     * Indica si la idea ya recibió al menos una postulación. Mientras no tenga
     * ninguna, el beneficiario todavía puede eliminarla.
     */
    public function hasApplications(): bool
    {
        return $this->assignments()->exists();
    }

    /**
     * Una idea puede cancelarse mientras no esté terminada ni ya cancelada.
     */
    public function canBeCancelled(): bool
    {
        return ! in_array($this->status, ['cancelled', 'completed'], true);
    }

    /**
     * Cancela la idea conservando el historial de postulaciones y avances.
     * El motivo queda registrado como auditoría y las postulaciones pendientes
     * se rechazan para cerrar la convocatoria.
     */
    public function cancel(string $reason): bool
    {
        return DB::transaction(function () use ($reason): bool {
            $idea = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! $idea->canBeCancelled()) {
                return false;
            }

            $idea->assignments()
                ->where('status', Assignment::STATUS_APPLIED)
                ->update(['status' => Assignment::STATUS_REJECTED]);

            $idea->update([
                'status' => 'cancelled',
                'cancellation_reason' => trim($reason),
                'cancelled_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Revisa si ya se cumplieron las condiciones para pasar a desarrollo:
     * cupo definido, cupo completo de postulantes y todos aceptados.
     */
    public function syncDevelopmentStatus(): void
    {
        if ($this->max_students === null || ! in_array($this->status, ['available', 'in_development'], true)) {
            return;
        }

        $active = $this->assignments()
            ->whereNotIn('status', Assignment::INACTIVE_STATUSES)
            ->count();

        $accepted = $this->assignments()
            ->whereIn('status', Assignment::ACCEPTED_STATUSES)
            ->count();

        if ($active >= $this->max_students && $accepted >= $this->max_students) {
            $this->markAsInDevelopment();

            return;
        }

        // Un proyecto iniciado con el cupo completo vuelve a estar disponible
        // si se libera un lugar. Uno iniciado a mano con menos integrantes se
        // mantiene en desarrollo mientras conserve el mínimo requerido.
        $minimum = $this->started_early ? self::MIN_STUDENTS : $this->max_students;

        if ($this->status === 'in_development' && $accepted < $minimum) {
            $this->update([
                'status' => 'available',
                'started_early' => false,
            ]);
        }
    }

    /**
     * Postulaciones vigentes de la idea (las rechazadas no ocupan cupo).
     */
    public function activeAssignmentsCount(): int
    {
        if (array_key_exists('active_assignments_count', $this->attributes)) {
            return (int) $this->attributes['active_assignments_count'];
        }

        return $this->assignments()->whereNotIn('status', Assignment::INACTIVE_STATUSES)->count();
    }

    /**
     * Indica si la idea todavía tiene cupo para nuevas postulaciones.
     * Sin cupo definido por el profesor no se aceptan postulaciones.
     */
    public function hasAvailableSlots(): bool
    {
        return $this->max_students !== null
            && $this->activeAssignmentsCount() < $this->max_students;
    }

    /**
     * Cantidad de postulaciones aceptadas (incluye las que ya están en progreso).
     */
    public function acceptedAssignmentsCount(): int
    {
        if (array_key_exists('accepted_assignments_count', $this->attributes)) {
            return (int) $this->attributes['accepted_assignments_count'];
        }

        return $this->assignments()
            ->whereIn('status', Assignment::ACCEPTED_STATUSES)
            ->count();
    }

    /**
     * Indica si la idea ya alcanzó el máximo de archivos adjuntos permitidos.
     */
    public function hasReachedAttachmentLimit(): bool
    {
        return $this->attachments()->count() >= self::MAX_ATTACHMENTS;
    }

    /**
     * Filtra ideas por etiqueta exacta, nombre de la idea o de quien la publicó.
     *
     * Si el texto coincide con una etiqueta existente, se muestran únicamente
     * las ideas que tienen esa etiqueta.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $tag = Tag::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($term)])
            ->first();

        if ($tag !== null) {
            return $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey($tag->id));
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('title', 'like', '%'.$term.'%')
                ->orWhereHas(
                    'organizer',
                    fn (Builder $organizer) => $organizer->where('name', 'like', '%'.$term.'%'),
                );
        });
    }

    /**
     * Al eliminar la idea, elimina sus adjuntos mediante Eloquent para que el
     * modelo Attachment borre cada archivo físico del disco. Las filas que
     * queden se eliminan por la FK en cascada.
     */
    protected static function booted(): void
    {
        static::deleting(function (ProjectIdea $idea): void {
            $idea->updates()->get()->each(function (ProjectUpdate $update): void {
                $update->delete();
            });

            $idea->attachments()->get()->each(function (Attachment $attachment): void {
                $attachment->delete();
            });
        });
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'project_idea_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'project_idea_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class, 'project_idea_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'project_idea_tag')->withTimestamps();
    }
}
