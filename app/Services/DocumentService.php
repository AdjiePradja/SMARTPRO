<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates document creation and content persistence. Business logic lives
 * here rather than in the controller (CLAUDE.md §3).
 */
class DocumentService
{
    public function __construct(
        private readonly DocumentNumberService $numbering,
        private readonly AuditService $audit,
    ) {}

    /**
     * Create a new draft document (roadmap Task 2.3). The document number is
     * either auto-generated (PPA-ADRO-{TYPE}-{DEPT}-{NN}) or provided manually.
     */
    public function createDraft(
        User $creator,
        DocumentType $type,
        Department $department,
        string $title,
        ?string $manualNumber = null,
    ): Document {
        return DB::transaction(function () use ($creator, $type, $department, $title, $manualNumber) {
            $isManual = filled($manualNumber);

            $document = Document::create([
                'document_type_id' => $type->id,
                'department_id' => $department->id,
                'title' => $title,
                'doc_number' => $isManual ? $manualNumber : $this->numbering->generate($type, $department),
                'doc_number_manual' => $isManual,
                'status' => 'draft',
                'current_step' => 1,
                'created_by' => $creator->id,
            ]);

            // Primary author = the creator (the only signatory on pengesahan, §2.3).
            $document->authors()->create([
                'user_id' => $creator->id,
                'is_primary' => true,
            ]);

            $this->audit->log('document.create', $document->id, [
                'doc_number' => $document->doc_number,
                'type' => $type->code,
                'department' => $department->code,
            ]);

            return $document;
        });
    }

    /**
     * Persist content for one section (used by autosave and step submit).
     * Stored as value_json keyed by section — no per-type columns (PRD §11).
     */
    public function saveSection(Document $document, string $sectionKey, mixed $value): void
    {
        $document->contents()->updateOrCreate(
            ['section_key' => $sectionKey],
            ['value_json' => $value],
        );
    }

    /**
     * Revisi Tipe B (PRD v2 §3.3): pembaruan dokumen Berlaku (0→1→…).
     * Snapshot versi lama ke document_versions, set lama "Sedang Direvisi"
     * (masih Berlaku sementara), lalu buat versi baru (No. Revisi naik) dengan
     * salinan isi — direview dari awal (anotasi lama tidak dibawa).
     */
    public function requestRevision(Document $published, User $requester): Document
    {
        return DB::transaction(function () use ($published, $requester) {
            $this->snapshot($published);

            $published->update(['status' => 'sedang_direvisi']);

            $new = Document::create([
                'doc_number' => $published->doc_number,          // nomor diwariskan
                'doc_number_manual' => $published->doc_number_manual,
                'document_type_id' => $published->document_type_id,
                'department_id' => $published->department_id,
                'title' => $published->title,
                'status' => 'draft',
                'current_step' => 1,
                'revision_round' => 0,
                'no_revisi' => $published->no_revisi + 1,         // No. Revisi naik
                'edisi' => $published->edisi,
                'is_controlled' => $published->is_controlled,
                'created_by' => $requester->id,
            ]);

            // Salin isi dari versi lama supaya revisor mulai dari isi terakhir.
            foreach ($published->contents as $content) {
                $new->contents()->create([
                    'section_key' => $content->section_key,
                    'value_json' => $content->value_json,
                ]);
            }

            $new->authors()->create(['user_id' => $requester->id, 'is_primary' => true]);

            $this->audit->log('document.request_revision', $new->id, [
                'from_document_id' => $published->id,
                'no_revisi' => $new->no_revisi,
            ]);

            return $new;
        });
    }

    /** Simpan snapshot isi + meta dokumen ke document_versions (jejak audit). */
    public function snapshot(Document $document): void
    {
        $document->versions()->create([
            'no_revisi' => $document->no_revisi,
            'created_by' => $document->created_by,
            'snapshot_json' => [
                'title' => $document->title,
                'doc_number' => $document->doc_number,
                'no_revisi' => $document->no_revisi,
                'status' => $document->status,
                'reviewer_id' => $document->reviewer_id,
                'approver_id' => $document->approver_id,
                'published_at' => $document->published_at?->toDateTimeString(),
                'contents' => $document->contentMap(),
            ],
        ]);
    }
}
