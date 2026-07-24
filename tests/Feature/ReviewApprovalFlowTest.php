<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Full Fase 4 path: Staff buat -> GL tinjau (reject) -> revisi -> GL loloskan
 * -> Pimpinan setuju -> Berlaku.
 */
class ReviewApprovalFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_full_review_approval_flow(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();       // peninjau
        $pimpinan = User::where('nrp', 'PJO-0001')->firstOrFail(); // approver
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        // Staff creates + fills + chooses reviewer/approver.
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Uji Alur');
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Poin tujuan']]);
        $doc->update(['reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id, 'current_step' => 2]);

        // Kirim -> waiting_for_review (masih bisa ditarik)
        $this->actingAs($gl)->post(route('documents.submit', $doc))->assertRedirect();
        $this->assertSame('waiting_for_review', $doc->refresh()->status);

        // GL membuka dokumen -> in_review
        $this->actingAs($sh)->get(route('review.show', $doc))->assertOk();
        $this->assertSame('in_review', $doc->refresh()->status);

        // GL reviews & rejects with an annotation.
        $this->actingAs($sh)->post(route('review.store', $doc), [
            'decision' => 'reject',
            'summary' => 'Perlu perbaikan.',
            'annotations' => ['tujuan' => [0 => 'Perjelas poin tujuan ini.']],
        ])->assertRedirect();
        $doc->refresh();
        $this->assertSame('rejected', $doc->status);
        $this->assertSame(1, $doc->reviews()->count());
        $this->assertSame(1, $doc->reviews()->first()->annotations()->count());

        // Staff revises and resubmits -> revision_round++ and back to waiting_for_review.
        $this->actingAs($gl)->post(route('documents.submit', $doc))->assertRedirect();
        $doc->refresh();
        $this->assertSame('waiting_for_review', $doc->status);
        $this->assertSame(1, $doc->revision_round);

        // GL opens (->in_review) then passes it -> pending_approval.
        $this->actingAs($sh)->get(route('review.show', $doc));
        $this->actingAs($sh)->post(route('review.store', $doc), ['decision' => 'approve'])->assertRedirect();
        $this->assertSame('pending_approval', $doc->refresh()->status);

        // Pimpinan approves -> published/Berlaku + nomor final terkunci.
        $this->actingAs($pimpinan)->post(route('approvals.store', $doc), ['decision' => 'approve'])->assertRedirect();
        $doc->refresh();
        $this->assertSame('published', $doc->status);
        $this->assertNotNull($doc->published_at);
        $this->assertNotNull($doc->doc_number_final, 'nomor final dikunci saat approved');
        $this->assertSame(1, $doc->approvals()->where('decision', 'approved')->count());
    }
}
