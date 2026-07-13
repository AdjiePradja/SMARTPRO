<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('doc_number')->nullable()->index(); // PPA-ADRO-SOP-ICTMD-01
            $table->boolean('doc_number_manual')->default(false);
            $table->foreignId('document_type_id')->constrained();
            $table->foreignId('department_id')->index();
            $table->string('title');
            // State machine (5.1)
            $table->enum('status', [
                'draft', 'submitted', 'in_review', 'needs_revision',
                'pending_approval', 'published', 'archived', 'obsolete',
            ])->default('draft')->index();
            $table->unsignedInteger('current_step')->default(1);
            $table->unsignedInteger('revision_round')->default(0); // reject-driven revisions
            $table->unsignedInteger('no_revisi')->default(0);      // post-publish revisions (D9)
            $table->string('edisi')->nullable();
            $table->boolean('is_controlled')->default(true);       // terkendali vs tidak terkendali
            // Chosen participants (via dropdown at submit time)
            $table->foreignId('reviewer_id')->nullable()->index();
            $table->foreignId('approver_id')->nullable()->index();
            $table->foreignId('created_by')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
