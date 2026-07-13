<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doc_number', 'doc_number_manual', 'document_type_id', 'department_id',
        'title', 'status', 'current_step', 'revision_round', 'no_revisi',
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
        'in_review' => 'Dalam Peninjauan',
        'rejected' => 'Ditolak',
        'pending_approval' => 'Menunggu Persetujuan',
        'published' => 'Berlaku',
        'sedang_direvisi' => 'Sedang Direvisi',
        'obsolete' => 'Obsolete',
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

    /** Content as an associative array keyed by section_key. */
    public function contentMap(): array
    {
        return $this->contents->pluck('value_json', 'section_key')->toArray();
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
