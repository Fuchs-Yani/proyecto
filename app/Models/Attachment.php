<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'attachments';

    protected $fillable = [
        'project_idea_id',
        'file_name',
        'file_path',
        'file_type',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    /**
     * Borra el archivo físico cuando se elimina el registro y limpia el
     * archivo anterior cuando se reemplaza por uno nuevo.
     */
    protected static function booted(): void
    {
        static::creating(function (Attachment $attachment): void {
            if (blank($attachment->project_idea_id)) {
                return;
            }

            $count = static::query()
                ->where('project_idea_id', $attachment->project_idea_id)
                ->count();

            if ($count >= ProjectIdea::MAX_ATTACHMENTS) {
                throw ValidationException::withMessages([
                    'file' => 'No podés subir más de '.ProjectIdea::MAX_ATTACHMENTS.' archivos por idea.',
                ]);
            }
        });

        static::deleting(function (Attachment $attachment): void {
            if (filled($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });

        static::updating(function (Attachment $attachment): void {
            if ($attachment->isDirty('project_idea_id')) {
                $count = static::query()
                    ->where('project_idea_id', $attachment->project_idea_id)
                    ->whereKeyNot($attachment->getKey())
                    ->count();

                if ($count >= ProjectIdea::MAX_ATTACHMENTS) {
                    throw ValidationException::withMessages([
                        'file' => 'No podés mover el archivo a una idea que ya tiene '.ProjectIdea::MAX_ATTACHMENTS.' archivos.',
                    ]);
                }
            }

            if (! $attachment->isDirty('file_path')) {
                return;
            }

            $previousPath = $attachment->getOriginal('file_path');

            if (filled($previousPath)) {
                Storage::disk('public')->delete($previousPath);
            }
        });
    }

    public function projectIdea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'project_idea_id');
    }
}
