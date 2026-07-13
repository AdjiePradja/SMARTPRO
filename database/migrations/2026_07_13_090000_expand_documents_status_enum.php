<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Expand documents.status to the PRD v2 §3.2 set (adds `rejected` and
 * `sedang_direvisi`). Legacy values are retained so existing rows stay valid.
 * Non-destructive: only widens the allowed enum values.
 */
return new class extends Migration
{
    private array $statuses = [
        'draft', 'in_review', 'rejected', 'pending_approval',
        'published', 'sedang_direvisi', 'obsolete',
        // legacy (kept to avoid invalidating older rows)
        'submitted', 'needs_revision', 'archived',
    ];

    public function up(): void
    {
        $list = collect($this->statuses)->map(fn ($s) => "'{$s}'")->implode(',');
        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM({$list}) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('draft','submitted','in_review','needs_revision','pending_approval','published','archived','obsolete') NOT NULL DEFAULT 'draft'");
    }
};
