<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flexible per-section content — no per-doc-type columns needed (PRD §11)
        Schema::create('document_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('section_key')->index();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'section_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_contents');
    }
};
