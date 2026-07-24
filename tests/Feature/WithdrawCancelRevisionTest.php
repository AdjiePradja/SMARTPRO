<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WithdrawCancelRevisionTest extends TestCase
{
    use DatabaseTransactions;

    private function makeSubmitted(): array
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();
        $pimpinan = User::where('nrp', 'PJO-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Uji');
        $doc->update(['reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id, 'current_step' => 2]);
        $this->actingAs($gl)->post(route('documents.submit', $doc));

        return [$doc->refresh(), $gl, $sh];
    }

    public function test_withdraw_returns_waiting_document_to_draft(): void
    {
        [$doc, $gl] = $this->makeSubmitted();
        $this->assertSame('waiting_for_review', $doc->status);

        $this->actingAs($gl)->post(route('documents.withdraw', $doc))->assertRedirect();
        $this->assertSame('draft', $doc->refresh()->status);
    }

    /**
     * Kirim dari WIZARD (saveStep action=submit) juga harus waiting_for_review —
     * dulu langsung in_review sehingga GL tak pernah bisa Menarik dokumen.
     */
    public function test_wizard_submit_is_withdrawable_until_reviewed(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();
        $pimpinan = User::where('nrp', 'PJO-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Wizard Kirim');
        $doc->update(['current_step' => 2]);

        // Form wizard langkah 2 selalu mengirim user_picker peninjau/penyetuju.
        $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 2, 'action' => 'submit',
            'sections' => ['peninjau' => $sh->id, 'penyetuju' => $pimpinan->id],
        ])->assertRedirect();
        $this->assertSame('waiting_for_review', $doc->refresh()->status, 'wizard Kirim -> masih bisa Ditarik');

        $this->actingAs($gl)->post(route('documents.withdraw', $doc))->assertRedirect();
        $this->assertSame('draft', $doc->refresh()->status);
    }

    public function test_withdraw_blocked_once_in_review(): void
    {
        [$doc, , $sh] = $this->makeSubmitted();
        $this->actingAs($sh)->get(route('review.show', $doc)); // -> in_review
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();

        $this->actingAs($gl)->post(route('documents.withdraw', $doc))->assertForbidden();
        $this->assertSame('in_review', $doc->refresh()->status);
    }

    public function test_reviewer_can_cancel_revision(): void
    {
        [$doc, , $sh] = $this->makeSubmitted();
        $this->actingAs($sh)->get(route('review.show', $doc));
        $this->actingAs($sh)->post(route('review.store', $doc), ['decision' => 'reject', 'annotations' => ['tujuan' => [0 => 'perbaiki']]]);
        $this->assertSame('rejected', $doc->refresh()->status);

        // Batalkan Revisi -> kembali in_review.
        $this->actingAs($sh)->post(route('review.cancelRevision', $doc))->assertRedirect();
        $this->assertSame('in_review', $doc->refresh()->status);
    }
}
