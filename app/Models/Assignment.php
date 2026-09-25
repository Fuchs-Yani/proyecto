<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    protected $table = 'assignments';

    protected $fillable = [
        'project_idea_id',
        'student_id',
        'status',
        'applied_at',
        'started_at',
        'finished_at',
    ];

    public function projectIdea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'project_idea_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
