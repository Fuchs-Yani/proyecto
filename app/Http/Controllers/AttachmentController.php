<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\ProjectIdea;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    // POST /ideas/{projectIdea}/attachments - subir (org dueña o admin)
    public function store(StoreAttachmentRequest $request, ProjectIdea $projectIdea)
    {
        $this->authorize('create', Attachment::class);

        if (auth()->user()->hasRole('organization') && $projectIdea->organizer_id !== auth()->id()) {
            abort(403, 'Solo puedes subir archivos a tus propias ideas.');
        }

        $file = $request->file('file');
        $path = $file->store('attachments', 'public');

        Attachment::create([
            'project_idea_id' => $projectIdea->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'uploaded_at' => now(),
        ]);

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

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('status', 'Archivo eliminado.');
    }
}
