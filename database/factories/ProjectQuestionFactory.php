<?php

namespace Database\Factories;

use App\Models\ProjectIdea;
use App\Models\ProjectQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectQuestion>
 */
class ProjectQuestionFactory extends Factory
{
    protected $model = ProjectQuestion::class;

    public function definition(): array
    {
        return [
            'project_idea_id' => ProjectIdea::factory(),
            'asked_by' => User::factory(),
            'question' => fake()->sentence().'?',
            'answer' => null,
            'answered_by' => null,
            'answered_at' => null,
        ];
    }

    public function answered(): static
    {
        return $this->state(fn (): array => [
            'answer' => fake()->paragraph(),
            'answered_at' => now(),
        ]);
    }
}
