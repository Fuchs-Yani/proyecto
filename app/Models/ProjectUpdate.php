<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectUpdate extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STAGE_ANALYSIS = 'analisis';

    public const STAGE_DESIGN = 'diseno';

    public const STAGE_DEVELOPMENT = 'desarrollo';

    public const STAGE_TESTING = 'pruebas';

    public const STAGE_DELIVERY = 'entrega';

    /**
     * Etapas del proyecto, en el orden en que se espera completarlas.
     *
     * @var array<string, string>
     */
    public const STAGES = [
        self::STAGE_ANALYSIS => 'Análisis',
        self::STAGE_DESIGN => 'Diseño',
        self::STAGE_DEVELOPMENT => 'Desarrollo',
        self::STAGE_TESTING => 'Pruebas',
        self::STAGE_DELIVERY => 'Entrega',
    ];

    /**
     * Cantidad máxima de imágenes permitidas por avance.
     */
    public const MAX_IMAGES = 5;

    protected $table = 'project_updates';

    protected $fillable = [
        'assignment_id',
        'project_idea_id',
        'student_id',
        'description',
        'repository_url',
        'stage',
        'status',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Al eliminar el avance se eliminan sus imágenes y los archivos físicos.
     */
    protected static function booted(): void
    {
        static::deleting(function (ProjectUpdate $update): void {
            $update->images()->get()->each(function (ProjectUpdateImage $image): void {
                $image->delete();
            });
        });
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function projectIdea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'project_idea_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectUpdateImage::class, 'project_update_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Nombre legible de la etapa a la que pertenece el avance.
     */
    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? 'Sin etapa';
    }

    /**
     * Filtra avances por estado.
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return filled($status) ? $query->where('status', $status) : $query;
    }
}
