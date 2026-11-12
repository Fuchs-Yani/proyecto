<?php

namespace App\Http\Controllers;

use App\Models\ProjectIdea;
use App\Models\ProjectQuestion;
use App\Models\User;
use App\Notifications\ProjectQuestionAnswered;
use App\Notifications\ProjectQuestionAsked;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectQuestionController extends Controller
{
    /**
     * POST /ideas/{projectIdea}/preguntas
     * Un alumno del equipo aceptado pregunta al beneficiario.
     */
    public function store(Request $request, ProjectIdea $projectIdea): RedirectResponse
    {
        $this->authorize('askQuestion', $projectIdea);

        $data = $request->validate([
            'question' => ['required', 'string', 'max:5000'],
        ], [
            'question.required' => 'Escribí al menos una pregunta para el beneficiario.',
            'question.max' => 'El mensaje de preguntas no puede superar los 5000 caracteres.',
        ]);

        $question = $projectIdea->questions()->create([
            'asked_by' => auth()->id(),
            'question' => $data['question'],
        ]);

        $projectIdea->organizer?->notify(new ProjectQuestionAsked($question));

        return back()->with('status', 'Preguntas enviadas al beneficiario.');
    }

    /**
     * PATCH /preguntas/{projectQuestion}/responder
     * El beneficiario dueño responde la pregunta del equipo.
     */
    public function answer(Request $request, ProjectQuestion $projectQuestion): RedirectResponse
    {
        $projectIdea = $projectQuestion->projectIdea;

        abort_if($projectIdea === null, 404);

        $this->authorize('answerQuestion', $projectIdea);

        if ($projectQuestion->isAnswered()) {
            return back()->with('status', 'Estas preguntas ya fueron respondidas.');
        }

        $data = $request->validate([
            'answer' => ['required', 'string', 'max:8000'],
        ], [
            'answer.required' => 'Escribí la respuesta para el equipo.',
            'answer.max' => 'La respuesta no puede superar los 8000 caracteres.',
        ]);

        $projectQuestion->update([
            'answer' => $data['answer'],
            'answered_by' => auth()->id(),
            'answered_at' => now(),
        ]);

        $projectIdea->teamMembers()->each(
            fn (User $student) => $student->notify(new ProjectQuestionAnswered($projectQuestion)),
        );

        return back()->with('status', 'Respuesta enviada al equipo.');
    }

    /**
     * DELETE /preguntas/{projectQuestion}
     * El alumno puede retirar una pregunta que todavía no fue respondida.
     */
    public function destroy(ProjectQuestion $projectQuestion): RedirectResponse
    {
        $projectIdea = $projectQuestion->projectIdea;

        abort_if($projectIdea === null, 404);

        $this->authorize('deleteQuestion', [$projectIdea, $projectQuestion]);

        $projectQuestion->delete();

        return back()->with('status', 'Consulta eliminada.');
    }
}
