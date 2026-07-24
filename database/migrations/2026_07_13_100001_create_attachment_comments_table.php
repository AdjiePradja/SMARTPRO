<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Komentar pada foto lampiran (PRD v3.1 §6.2, ERD §14). Bisa diberikan saat
 * pembuatan & peninjauan. Additive — tabel baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachment_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attachment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->text('comment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachment_comments');
    }
};
