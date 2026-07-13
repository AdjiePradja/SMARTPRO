<?php

namespace App\Services\Ai;

use App\Models\Document;

/** Fallback when AI is disabled or no provider is configured. */
class NullReviewer implements AiReviewerInterface
{
    public function review(Document $document, array $contentMap): array
    {
        return [
            'summary' => 'Fitur AI Review sedang dinonaktifkan.',
            'findings' => [],
        ];
    }

    public function isEnabled(): bool
    {
        return false;
    }
}
