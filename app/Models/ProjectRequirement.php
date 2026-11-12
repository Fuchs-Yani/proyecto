<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRequirement extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'project_idea_id',
        'content',
        'status',
        'submitted_at',
        'beneficiary_approved_at',
        'beneficiary_approved_by',
        'professor_approved_at',
        'professor_approved_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'beneficiary_approved_at' => 'datetime',
            'professor_approved_at' => 'datetime',
        ];
    }

    public function projectIdea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'project_idea_id');
    }

    public function beneficiaryApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_approved_by');
    }

    public function professorApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_approved_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function hasBeneficiaryApproval(): bool
    {
        return $this->beneficiary_approved_at !== null;
    }

    public function hasProfessorApproval(): bool
    {
        return $this->professor_approved_at !== null;
    }

    /**
     * Guarda el borrador redactado por el equipo. Cualquier cambio posterior
     * invalida las aprobaciones anteriores.
     */
    public function saveDraft(string $content): void
    {
        $this->fill([
            'content' => trim($content),
            'status' => self::STATUS_DRAFT,
            'submitted_at' => null,
            'beneficiary_approved_at' => null,
            'beneficiary_approved_by' => null,
            'professor_approved_at' => null,
            'professor_approved_by' => null,
        ])->save();
    }

    /**
     * Envía el borrador para la revisión del beneficiario y del profesor.
     */
    public function submitForReview(): bool
    {
        if (! $this->isDraft() || trim((string) $this->content) === '') {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PENDING,
            'submitted_at' => now(),
            'beneficiary_approved_at' => null,
            'beneficiary_approved_by' => null,
            'professor_approved_at' => null,
            'professor_approved_by' => null,
        ]);

        return true;
    }

    /**
     * Registra la aprobación del beneficiario o del profesor. La lista queda
     * aprobada recién cuando ambas partes dieron el visto bueno.
     */
    public function approveBy(User $user): bool
    {
        $this->loadMissing('projectIdea');

        $isBeneficiary = $user->hasRole('beneficiario')
            && $this->projectIdea?->organizer_id === $user->id;
        $isProfessor = $user->hasAnyRole(['admin', 'profesor']);

        if (! $isBeneficiary && ! $isProfessor) {
            return false;
        }

        if ($isBeneficiary && ! $this->hasBeneficiaryApproval()) {
            $this->beneficiary_approved_at = now();
            $this->beneficiary_approved_by = $user->id;
        }

        if ($isProfessor && ! $this->hasProfessorApproval()) {
            $this->professor_approved_at = now();
            $this->professor_approved_by = $user->id;
        }

        if ($this->hasBeneficiaryApproval() && $this->hasProfessorApproval()) {
            $this->status = self::STATUS_APPROVED;
        }

        $this->save();

        return true;
    }
}
