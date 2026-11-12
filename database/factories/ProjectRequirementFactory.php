<?php

namespace Database\Factories;

use App\Models\ProjectIdea;
use App\Models\ProjectRequirement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectRequirement>
 */
class ProjectRequirementFactory extends Factory
{
    protected $model = ProjectRequirement::class;

    public function definition(): array
    {
        return [
            'project_idea_id' => ProjectIdea::factory(),
            'content' => fake()->paragraphs(3, true),
            'status' => ProjectRequirement::STATUS_DRAFT,
            'submitted_at' => null,
            'beneficiary_approved_at' => null,
            'beneficiary_approved_by' => null,
            'professor_approved_at' => null,
            'professor_approved_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => ProjectRequirement::STATUS_PENDING,
            'submitted_at' => now(),
        ]);
    }

    public function approved(?User $professor = null): static
    {
        return $this->state(fn (): array => [
            'status' => ProjectRequirement::STATUS_APPROVED,
            'submitted_at' => now()->subDays(2),
            'beneficiary_approved_at' => now()->subDay(),
            'professor_approved_at' => now(),
            'professor_approved_by' => $professor?->id,
        ]);
    }
}
