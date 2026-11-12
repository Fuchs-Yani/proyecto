<?php

namespace App\Notifications;

use App\Models\ProjectIdea;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectIdeaSubmitted extends Notification
{
    use Queueable;

    public function __construct(
        public ProjectIdea $projectIdea,
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
        return [
            'type' => 'project_idea_submitted',
            'title' => 'Nueva idea para revisar',
            'message' => 'El beneficiario publicó «'.$this->projectIdea->title.'». Definí el cupo para que los alumnos puedan postularse.',
            'url' => route('ideas.show', $this->projectIdea),
            'project_idea_id' => $this->projectIdea->getKey(),
        ];
    }
}
