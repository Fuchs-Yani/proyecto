<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $table = 'attachments';

    protected $fillable = [
        'project_idea_id',
        'file_name',
        'file_path',
        'file_type',
        'uploaded_at',
    ];

    public function projectIdea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'project_idea_id');
    }
}