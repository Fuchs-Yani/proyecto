<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'project_idea_id' => ProjectIdea::factory(),
            'student_id' => User::factory(),
            'status' => 'applied',
            'applied_at' => now(),
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
