<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{ProjectIdeaController,AssignmentController,AttachmentController};

Route::get('/', function () {
    $finished = App\Models\ProjectIdea::where('status', 'completed')->with('organizer')->latest()->take(3)->get();

    return view('welcome', compact('finished'));
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // TODOS los roles: ver ideas
    Route::get('/ideas', [ProjectIdeaController::class, 'index'])->name('ideas.index');
    Route::get('/ideas/{projectIdea}', [ProjectIdeaController::class, 'show'])->name('ideas.show');
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    // STUDENT (+admin): postularse / retirar
    Route::post('/ideas/{projectIdea}/apply', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
    // ORGANIZATION (+admin): subir / borrar adjuntos propios
    Route::post('/ideas/{projectIdea}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
});

require __DIR__.'/auth.php';
