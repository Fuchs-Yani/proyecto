<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_idea_id',
        'asked_by',
        'question',
        'answer',
        'answered_by',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
        ];
    }

    public function projectIdea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'project_idea_id');
    }

    /**
     * Alumno del equipo que realizó la pregunta.
     */
    public function asker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asked_by');
    }

    /**
     * Beneficiario que respondió la pregunta.
     */
    public function answerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }
}
