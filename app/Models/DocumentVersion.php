<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $fillable = ['document_id', 'no_revisi', 'snapshot_json', 'created_by'];

    protected $casts = ['snapshot_json' => 'array'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
