<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectIdea extends Model
{
    use HasFactory;

    /**
     * Cantidad máxima de archivos adjuntos permitidos por idea.
     */
    public const MAX_ATTACHMENTS = 2;

    protected $table = 'project_ideas';

    protected $fillable = [
        'organizer_id',
        'title',
        'description',
        'status',
        'max_students',
    ];

    protected function casts(): array
    {
        return [
            'max_students' => 'integer',
        ];
    }

    /**
     * Indica si la idea todavía acepta postulaciones.
     */
    public function isOpenForApplications(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Postulaciones vigentes de la idea (las rechazadas no ocupan cupo).
     */
    public function activeAssignmentsCount(): int
    {
        if (array_key_exists('active_assignments_count', $this->attributes)) {
            return (int) $this->attributes['active_assignments_count'];
        }

        return $this->assignments()->where('status', '!=', 'rejected')->count();
    }

    /**
     * Indica si la idea todavía tiene cupo para nuevas postulaciones.
     * Cuando max_students es null no hay límite.
     */
    public function hasAvailableSlots(): bool
    {
        return $this->max_students === null
            || $this->activeAssignmentsCount() < $this->max_students;
    }

    /**
     * Indica si la idea ya alcanzó el máximo de archivos adjuntos permitidos.
     */
    public function hasReachedAttachmentLimit(): bool
    {
        return $this->attachments()->count() >= self::MAX_ATTACHMENTS;
    }

    /**
     * Al eliminar la idea, elimina sus adjuntos mediante Eloquent para que el
     * modelo Attachment borre cada archivo físico del disco. Las filas que
     * queden se eliminan por la FK en cascada.
     */
    protected static function booted(): void
    {
        static::deleting(function (ProjectIdea $idea): void {
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
}
