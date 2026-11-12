<?php

namespace App\Notifications;

use App\Models\ProjectIdea;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectRequirementApproved extends Notification
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
            'type' => 'project_requirement_approved',
            'title' => 'Requerimientos aprobados',
            'message' => 'El beneficiario y el profesor aprobaron los requerimientos de «'.$this->projectIdea->title.'».',
            'url' => route('ideas.show', $this->projectIdea),
            'project_idea_id' => $this->projectIdea->getKey(),
        ];
    }
}
