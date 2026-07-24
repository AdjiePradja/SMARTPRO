<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * v3-rev3 #4: penyetuju (PJO) mendapat form tinjau versi "ajukan revisi" —
 * bisa memberi catatan per-item yang tampil di form revisi pembuat.
 */
class ApproverRevisionTest extends TestCase
{
    use DatabaseTransactions;

    private function pendingApprovalDoc(): array
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();
        $pjo = User::where('nrp', 'PJO-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Approver Revisi');
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan satu', 'Tujuan dua']]);
        $doc->update(['status' => 'pending_approval', 'reviewer_id' => $sh->id, 'approver_id' => $pjo->id]);

        return [$doc, $gl, $pjo];
    }

    /**
     * Halaman PJO RINGKAS: keputusan + alasan saja. Penilaian per-item (termasuk
     * analisa JSA) adalah tugas peninjau — TIDAK diduplikasi di halaman approver.
     */
    public function test_approval_page_is_decision_only(): void
    {
        [$doc, , $pjo] = $this->pendingApprovalDoc();

        $this->actingAs($pjo)->get(route('approvals.show', $doc))
            ->assertOk()
            ->assertSee('Kembalikan untuk Revisi')
            ->assertSee('Setujui')
            ->assertDontSee('annotations[', false);   // tanpa kotak catatan per-item
    }

    public function test_reject_requires_reason(): void
    {
        [$doc, , $pjo] = $this->pendingApprovalDoc();

        // Tanpa ringkasan & tanpa catatan → ditolak validasi, dokumen tetap pending.
        $this->actingAs($pjo)->post(route('approvals.store', $doc), ['decision' => 'reject'])
            ->assertSessionHasErrors('summary');
        $this->assertSame('pending_approval', $doc->refresh()->status);
    }

    /** Alasan revisi dari PJO harus tampil di form revisi pembuat. */
    public function test_approver_reason_appears_in_creator_revision_form(): void
    {
        [$doc, $gl, $pjo] = $this->pendingApprovalDoc();

        $this->actingAs($pjo)->post(route('approvals.store', $doc), [
            'decision' => 'reject',
            'summary' => 'Tujuan kedua kurang tepat, perjelas.',
        ])->assertRedirect();

        $doc->refresh();
        $this->assertSame('rejected', $doc->status, 'dokumen kembali ke pembuat');

        // Alasan tersimpan bertanda [Penyetuju] agar pembuat tahu asalnya.
        $review = $doc->reviews()->where('decision', 'needs_revision')->latest('id')->firstOrFail();
        $this->assertStringStartsWith('[Penyetuju]', $review->summary);

        // Tampil di form revisi (edit) milik pembuat.
        $this->actingAs($gl)->get(route('documents.edit', $doc))
            ->assertOk()
            ->assertSee('Tujuan kedua kurang tepat, perjelas.');
    }

    public function test_approve_still_publishes(): void
    {
        [$doc, , $pjo] = $this->pendingApprovalDoc();

        $this->actingAs($pjo)->post(route('approvals.store', $doc), ['decision' => 'approve'])->assertRedirect();
        $this->assertSame('published', $doc->refresh()->status);
    }
}
