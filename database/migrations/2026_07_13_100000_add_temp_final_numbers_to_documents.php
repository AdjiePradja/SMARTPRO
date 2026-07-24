<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penomoran sementara vs final (PRD v3.1 §5). `doc_number_temp` dipakai saat
 * pembuatan/review (boleh bolong); `doc_number_final` dikunci saat approved
 * (tak bolong). Kolom `doc_number` lama dipertahankan (dormant) — additive,
 * non-destruktif; data lama di-backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('doc_number_temp')->nullable()->after('id')->index();
            $table->string('doc_number_final')->nullable()->after('doc_number_temp')->index();
        });

        // Backfill: semua dapat temp dari doc_number lama; yang sudah terbit dapat final.
        DB::table('documents')->update(['doc_number_temp' => DB::raw('doc_number')]);
        DB::table('documents')->whereIn('status', ['published', 'sedang_direvisi', 'obsolete'])
            ->update(['doc_number_final' => DB::raw('doc_number')]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['doc_number_temp', 'doc_number_final']);
        });
    }
};
