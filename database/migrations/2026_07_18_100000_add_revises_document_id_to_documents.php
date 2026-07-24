<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda draft REVISI Tipe B: menunjuk dokumen Berlaku yang sedang direvisinya.
 * Dipakai untuk memunculkan tahap-3 wizard (form log revisi) & halaman CATATAN
 * REVISI. Aditif — tidak mengubah/menghapus data (CLAUDE.md §4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('revises_document_id')->nullable()->after('no_revisi')
                ->constrained('documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revises_document_id');
        });
    }
};
