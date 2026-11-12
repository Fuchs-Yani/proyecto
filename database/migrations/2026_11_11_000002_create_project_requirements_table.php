<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_idea_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('beneficiary_approved_at')->nullable();
            $table->foreignId('beneficiary_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('professor_approved_at')->nullable();
            $table->foreignId('professor_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_requirements');
    }
};
