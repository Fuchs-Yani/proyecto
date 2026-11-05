<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectUpdateImage extends Model
{
    use HasFactory;

    protected $table = 'project_update_images';

    protected $fillable = [
        'project_update_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * Borra el archivo físico cuando se elimina el registro.
     */
    protected static function booted(): void
    {
        static::deleting(function (ProjectUpdateImage $image): void {
            if (filled($image->file_path)) {
                Storage::disk('public')->delete($image->file_path);
            }
        });
    }

    public function projectUpdate(): BelongsTo
    {
        return $this->belongsTo(ProjectUpdate::class, 'project_update_id');
    }

    /**
     * URL pública de la imagen.
     */
    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
