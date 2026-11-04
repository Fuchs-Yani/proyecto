<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\ProjectIdea;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttachmentController extends Controller
{
    // POST /ideas/{projectIdea}/attachments - subir (org dueña o admin)
    public function store(StoreAttachmentRequest $request, ProjectIdea $projectIdea)
    {
        $this->authorize('create', Attachment::class);

        if (auth()->user()->hasRole('beneficiario') && $projectIdea->organizer_id !== auth()->id()) {
            abort(403, 'Solo puedes subir archivos a tus propias ideas.');
        }

        if ($projectIdea->hasReachedAttachmentLimit()) {
            throw ValidationException::withMessages([
                'file' => 'No podés subir más de '.ProjectIdea::MAX_ATTACHMENTS.' archivos por idea.',
            ]);
        }

        $file = $request->file('file');
        $path = $file->store('attachments', 'public');

        try {
            Attachment::create([
                'project_idea_id' => $projectIdea->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'uploaded_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            // Si falla el guardado en la base, no deja el archivo huérfano en el disco.
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        return back()->with('status', 'Archivo subido.');
    }

    // GET /attachments/{attachment}/download
    public function download(Attachment $attachment)
    {
        $this->authorize('view', $attachment);

        if (! Storage::disk('public')->exists($attachment->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    // DELETE /attachments/{attachment}
    public function destroy(Attachment $attachment)
    {
        $this->authorize('delete', $attachment);

        // El modelo Attachment elimina el archivo físico al borrar el registro.
        $attachment->delete();

        return back()->with('status', 'Archivo eliminado.');
    }
}
