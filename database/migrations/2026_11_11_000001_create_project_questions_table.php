<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_idea_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asked_by')->constrained('users')->cascadeOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['project_idea_id', 'answered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_questions');
    }
};
