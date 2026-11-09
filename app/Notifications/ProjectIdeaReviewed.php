<?php

namespace App\Notifications;

use App\Models\ProjectIdea;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectIdeaReviewed extends Notification
{
    use Queueable;

    public function __construct(
        public ProjectIdea $projectIdea,
        public int $maxStudents,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $students = $this->maxStudents === 1 ? 'alumno' : 'alumnos';

        return [
            'type' => 'project_idea_reviewed',
            'title' => 'Idea revisada',
            'message' => 'El profesor revisó «'.$this->projectIdea->title.'» y definió un cupo de '
                .$this->maxStudents.' '.$students.'.',
            'url' => route('ideas.show', $this->projectIdea),
            'project_idea_id' => $this->projectIdea->getKey(),
            'max_students' => $this->maxStudents,
        ];
    }
}
