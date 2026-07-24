<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doc_number', 'doc_number_temp', 'doc_number_final', 'doc_number_manual', 'document_type_id', 'department_id',
        'title', 'status', 'current_step', 'revision_round', 'no_revisi', 'revises_document_id',
        'edisi', 'is_controlled', 'reviewer_id', 'approver_id', 'created_by',
        'submitted_at', 'published_at',
    ];

    protected $casts = [
        'doc_number_manual' => 'boolean',
        'is_controlled' => 'boolean',
        'submitted_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'waiting_for_review' => 'Menunggu Ditinjau',
        'in_review' => 'Dalam Peninjauan',
        'rejected' => 'Ditolak',
        'pending_approval' => 'Menunggu Persetujuan',
        'published' => 'Berlaku',
        'sedang_direvisi' => 'Sedang Direvisi',
        'obsolete' => 'Tidak Berlaku',
        // legacy
        'submitted' => 'Submitted',
        'needs_revision' => 'Perlu Revisi',
        'archived' => 'Diarsipkan',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(DocumentContent::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(DocumentAuthor::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Visibilitas "Status Dokumen" (v3.1 §3.3): dokumen yang dibuat siapa pun
     * di LEVEL JABATAN yang sama, dalam DEPARTEMEN yang sama. Dipakai di menu
     * Status Dokumen (Fase 6). Pimpinan (tanpa dept) → hanya dokumennya sendiri.
     */
    public function scopeCreatedBySameLevel(Builder $query, User $user): Builder
    {
        if (! $user->department_id) {
            return $query->where('created_by', $user->id);
        }

        return $query->whereIn('created_by', User::where('jabatan', $user->jabatan)
            ->where('department_id', $user->department_id)->pluck('id'));
    }

    /**
     * Visibilitas "Status Dokumen Staff" read-only untuk GL & Section Head
     * (v3.1 §3.3): dokumen milik STAFF di departemen tsb. Tanpa hak edit/kirim/hapus.
     */
    public function scopeCreatedByStaffOfDept(Builder $query, ?int $departmentId): Builder
    {
        return $query->whereIn('created_by', User::where('jabatan', User::JABATAN_STAFF)
            ->where('department_id', $departmentId)->pluck('id'));
    }

    /** Content as an associative array keyed by section_key. */
    public function contentMap(): array
    {
        return $this->contents->pluck('value_json', 'section_key')->toArray();
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /** Nomor final bila sudah terbit, selain itu nomor sementara (v3.1 §5). */
    public function displayNumber(): string
    {
        return $this->doc_number_final ?? $this->doc_number_temp ?? $this->doc_number ?? '—';
    }

    public function hasFinalNumber(): bool
    {
        return filled($this->doc_number_final);
    }

    /** Draft revisi Tipe B? (menunjuk dokumen Berlaku yang direvisinya) */
    public function isRevisionDraft(): bool
    {
        return $this->revises_document_id !== null;
    }
}
