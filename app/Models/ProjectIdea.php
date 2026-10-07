<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectIdea extends Model
{
    use HasFactory;
    protected $table = 'project_ideas';

    protected $fillable = [
        'organizer_id',
        'title',
        'description',
        'status',
    ];

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
