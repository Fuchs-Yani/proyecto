<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProjectIdea extends Model
{
    use HasFactory;

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
     * Al eliminar la idea, borra también los archivos físicos de sus adjuntos.
     * Las filas de adjuntos y postulaciones se eliminan por la FK en cascada.
     */
    protected static function booted(): void
    {
        static::deleting(function (ProjectIdea $idea): void {
            foreach ($idea->attachments()->get() as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
            }
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
