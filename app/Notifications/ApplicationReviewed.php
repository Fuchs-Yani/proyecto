<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationReviewed extends Notification
{
    use Queueable;

    public function __construct(
        public Assignment $assignment,
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
        $accepted = $this->status === Assignment::STATUS_ACCEPTED;
        $title = $this->assignment->projectIdea?->title ?? 'el proyecto';

        return [
            'type' => 'application_reviewed',
            'title' => $accepted ? 'Postulación aceptada' : 'Postulación rechazada',
            'message' => $accepted
                ? 'Tu postulación al proyecto «'.$title.'» fue aceptada.'
                : 'Tu postulación al proyecto «'.$title.'» fue rechazada.',
            'url' => route('assignments.index'),
            'assignment_id' => $this->assignment->getKey(),
            'status' => $this->status,
        ];
    }
}
