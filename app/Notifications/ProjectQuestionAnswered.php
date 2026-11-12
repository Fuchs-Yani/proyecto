<?php

namespace App\Notifications;

use App\Models\ProjectQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectQuestionAnswered extends Notification
{
    use Queueable;

    public function __construct(
        public ProjectQuestion $question,
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
        $title = $this->question->projectIdea?->title ?? 'el proyecto';

        return [
            'type' => 'project_question_answered',
            'title' => 'Respuesta del beneficiario',
            'message' => 'El beneficiario respondió las consultas de «'.$title.'».',
            'url' => route('ideas.show', $this->question->project_idea_id),
            'project_idea_id' => $this->question->project_idea_id,
            'project_question_id' => $this->question->getKey(),
        ];
    }
}
