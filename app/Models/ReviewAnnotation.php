<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewAnnotation extends Model
{
    protected $fillable = [
        'review_id', 'section_key', 'item_ref', 'severity',
        'comment', 'ai_generated', 'ai_adopted', 'resolved',
    ];

    protected $casts = [
        'ai_generated' => 'boolean',
        'ai_adopted' => 'boolean',
        'resolved' => 'boolean',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
