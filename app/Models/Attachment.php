<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = ['document_id', 'section_key', 'path', 'original_name', 'mime', 'size'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttachmentComment::class);
    }
}
