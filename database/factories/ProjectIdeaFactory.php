<?php

namespace Database\Factories;

use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectIdea>
 */
class ProjectIdeaFactory extends Factory
{
    protected $model = ProjectIdea::class;

    public function definition(): array
    {
        return [
            'organizer_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'available',
        ];
    }
}
