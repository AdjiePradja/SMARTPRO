<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->index();
            $table->unsignedInteger('revision_round')->default(0);
            $table->enum('decision', ['pending', 'approved', 'needs_revision'])->default('pending');
            $table->text('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('review_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->string('section_key')->index();
            $table->string('item_ref')->nullable(); // pointer to specific item within a section
            $table->enum('severity', ['info', 'minor', 'major', 'critical'])->default('minor');
            $table->text('comment');
            $table->boolean('ai_generated')->default(false);
            $table->boolean('ai_adopted')->default(false);
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_annotations');
        Schema::dropIfExists('reviews');
    }
};
