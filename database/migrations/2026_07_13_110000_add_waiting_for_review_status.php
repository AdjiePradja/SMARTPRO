<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambah status `waiting_for_review` (PRD v3.1 §4.1): dikirim tapi belum
 * disentuh reviewer (pembuat masih bisa Tarik). Additive — memperluas enum.
 */
return new class extends Migration
{
    private array $statuses = [
        'draft', 'waiting_for_review', 'in_review', 'rejected', 'pending_approval',
        'published', 'sedang_direvisi', 'obsolete',
        // legacy
        'submitted', 'needs_revision', 'archived',
    ];

    public function up(): void
    {
        $list = collect($this->statuses)->map(fn ($s) => "'{$s}'")->implode(',');
        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM({$list}) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('draft','in_review','rejected','pending_approval','published','sedang_direvisi','obsolete','submitted','needs_revision','archived') NOT NULL DEFAULT 'draft'");
    }
};
