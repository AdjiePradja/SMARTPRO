<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Revisi Tipe B (PRD v2 §3.3): dokumen Berlaku 0 -> 1, versi lama disimpan &
 * jadi obsolete setelah versi baru approved.
 */
class RevisionTypeBTest extends TestCase
{
    use DatabaseTransactions;

    public function test_revision_type_b_zero_to_one(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();        // pembuat + pengaju revisi
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();        // peninjau (pembuat GL)
        $pimpinan = User::where('nrp', 'PJO-0001')->firstOrFail(); // approver
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $svc = app(DocumentService::class);

        // Buat dokumen sudah Berlaku (No. Revisi 0).
        $doc = $svc->createDraft($gl, $type, $gl->department, 'SOP Berlaku');
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan asli']]);
        $doc->update(['status' => 'published', 'published_at' => now(), 'reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id]);

        // GL Ajukan Revisi.
        $this->actingAs($gl)->post(route('documents.requestRevision', $doc))->assertRedirect();

        $doc->refresh();
        $this->assertSame('sedang_direvisi', $doc->status, 'versi lama jadi Sedang Direvisi');
        $this->assertSame(1, $doc->versions()->count(), 'snapshot versi lama tersimpan');

        $new = \App\Models\Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();
        $this->assertSame(1, $new->no_revisi, 'No. Revisi naik ke 1');
        $this->assertSame('draft', $new->status);
        $this->assertSame(['Tujuan asli'], $new->contentMap()['tujuan'], 'isi disalin dari versi lama');

        // Versi baru: pilih peninjau/approver, kirim, review, approve.
        $new->update(['reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id]);
        $this->actingAs($gl)->post(route('documents.submit', $new))->assertRedirect();
        $this->actingAs($sh)->post(route('review.store', $new), ['decision' => 'approve'])->assertRedirect();
        $this->actingAs($pimpinan)->post(route('approvals.store', $new), ['decision' => 'approve'])->assertRedirect();

        $new->refresh();
        $doc->refresh();
        $this->assertSame('published', $new->status, 'versi baru Berlaku');
        $this->assertSame('obsolete', $doc->status, 'versi lama jadi obsolete');
    }
}
