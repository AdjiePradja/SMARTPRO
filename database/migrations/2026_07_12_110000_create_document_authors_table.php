<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-author support (PRD v2 §2.3). The primary author (is_primary=true) is
 * the user who pressed "Buat Dokumen" and is the only one signed on the
 * pengesahan page; additional authors are recorded here for the log only.
 *
 * Additive & non-destructive — creates a new table, touches no existing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['document_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_authors');
    }
};
