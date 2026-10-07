<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\ProjectIdea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        return [
            'project_idea_id' => ProjectIdea::factory(),
            'file_name' => fake()->word().'.pdf',
            'file_path' => 'attachments/'.fake()->uuid().'.pdf',
            'file_type' => 'pdf',
            'uploaded_at' => now(),
        ];
    }
}
