<?php

namespace App\Notifications;

use App\Models\ProjectIdea;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectRequirementSubmitted extends Notification
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
            'type' => 'project_requirement_submitted',
            'title' => 'Requerimientos para aprobar',
            'message' => 'El equipo de «'.$this->projectIdea->title.'» envió la lista de requerimientos para tu aprobación.',
            'url' => route('ideas.show', $this->projectIdea),
            'project_idea_id' => $this->projectIdea->getKey(),
        ];
    }
}
