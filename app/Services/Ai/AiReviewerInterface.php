<?php

namespace App\Services\Ai;

use App\Models\Document;

/**
 * Provider abstraction for AI review assist (D10). Swapping providers means
 * swapping the binding in a service provider — not rewriting call sites.
 *
 * The AI never approves or rejects. It only produces structured suggestions;
 * the human reviewer adopts, customises, or rejects them (PRD §8).
 */
interface AiReviewerInterface
{
    /**
     * Analyse a document's content and return structured suggestions.
     *
     * @return array{summary: string, findings: array<int, array{section_key:string, severity:string, issue:string, suggestion:string}>}
     */
    public function review(Document $document, array $contentMap): array;

    public function isEnabled(): bool;
}
