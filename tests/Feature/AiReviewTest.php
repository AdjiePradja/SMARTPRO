<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\Ai\AiReviewerInterface;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AiReviewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ai_analyze_returns_findings_from_provider(): void
    {
        config(['services.ai.enabled' => true]);

        // Swap the provider for a deterministic fake (no network call).
        $this->app->instance(AiReviewerInterface::class, new class implements AiReviewerInterface
        {
            public function isEnabled(): bool
            {
                return true;
            }

            public function review(\App\Models\Document $document, array $contentMap): array
            {
                return [
                    'summary' => 'Ringkasan uji.',
                    'findings' => [
                        ['section_key' => 'tujuan', 'severity' => 'minor', 'issue' => 'Kurang spesifik', 'suggestion' => 'Perjelas cakupan tujuan.'],
                    ],
                ];
            }
        });

        $staff = User::where('nrp', 'STF-0001')->firstOrFail();
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($staff, $type, $staff->department, 'SOP AI');
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan']]);
        $doc->update(['status' => 'in_review', 'reviewer_id' => $gl->id, 'submitted_at' => now()]);

        $this->actingAs($gl)
            ->postJson(route('review.ai', $doc))
            ->assertOk()
            ->assertJson([
                'enabled' => true,
                'summary' => 'Ringkasan uji.',
                'findings' => [['section_key' => 'tujuan', 'suggestion' => 'Perjelas cakupan tujuan.']],
            ]);
    }

    public function test_ai_disabled_returns_gracefully(): void
    {
        config(['services.ai.enabled' => false]);

        $staff = User::where('nrp', 'STF-0001')->firstOrFail();
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($staff, $type, $staff->department, 'SOP AI Off');
        $doc->update(['status' => 'in_review', 'reviewer_id' => $gl->id]);

        $this->actingAs($gl)
            ->postJson(route('review.ai', $doc))
            ->assertOk()
            ->assertJson(['enabled' => false, 'findings' => []]);
    }
}
