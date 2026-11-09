<?php

namespace App\Notifications;

use App\Models\ProjectUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectUpdateReviewed extends Notification
{
    use Queueable;

    public function __construct(
        public ProjectUpdate $projectUpdate,
        public string $status,
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
        $approved = $this->status === ProjectUpdate::STATUS_APPROVED;
        $title = $this->projectUpdate->projectIdea?->title ?? 'el proyecto';

        return [
            'type' => 'project_update_reviewed',
            'title' => $approved ? 'Avance aprobado' : 'Avance rechazado',
            'message' => $approved
                ? 'Tu avance del proyecto «'.$title.'» fue aprobado.'
                : 'Tu avance del proyecto «'.$title.'» fue rechazado.',
            'url' => route('assignments.index'),
            'project_update_id' => $this->projectUpdate->getKey(),
            'status' => $this->status,
        ];
    }
}
