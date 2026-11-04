<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('project_idea_tag', function (Blueprint $table) {
            $table->foreignId('project_idea_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_idea_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_idea_tag');
        Schema::dropIfExists('tags');
    }
};
