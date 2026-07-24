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
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();        // pembuat
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();        // peninjau + pengaju revisi (GL tak lagi berhak)
        $pimpinan = User::where('nrp', 'PJO-0001')->firstOrFail(); // approver
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $svc = app(DocumentService::class);

        // Buat dokumen sudah Berlaku (No. Revisi 0).
        $doc = $svc->createDraft($gl, $type, $gl->department, 'SOP Berlaku');
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan asli']]);
        $doc->update(['status' => 'published', 'published_at' => now(), 'reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id]);

        // SH Ajukan Revisi (GL sebagai pembuat tak lagi berhak).
        $this->actingAs($sh)->post(route('documents.requestRevision', $doc))->assertRedirect();

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
        $this->actingAs($sh)->get(route('review.show', $new)); // waiting_for_review -> in_review
        $this->actingAs($sh)->post(route('review.store', $new), ['decision' => 'approve'])->assertRedirect();
        $this->actingAs($pimpinan)->post(route('approvals.store', $new), ['decision' => 'approve'])->assertRedirect();

        $new->refresh();
        $doc->refresh();
        $this->assertSame('published', $new->status, 'versi baru Berlaku');
        $this->assertSame('obsolete', $doc->status, 'versi lama jadi obsolete');
    }

    /** Roll-over: revisi 0..4; revisi berikutnya = Edisi+1 Rev 0 (angka 5 tak pernah tampil). */
    public function test_revision_rolls_over_to_next_edition_at_five(): void
    {
        $this->assertSame([1, 1], DocumentService::nextEditionRevision(1, 0));
        $this->assertSame([1, 4], DocumentService::nextEditionRevision(1, 3));
        $this->assertSame([2, 0], DocumentService::nextEditionRevision(1, 4), 'rev 4 -> Edisi 2 Rev 0');
        $this->assertSame([3, 0], DocumentService::nextEditionRevision(2, 4));

        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();
        $pimpinan = User::where('nrp', 'PJO-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $svc = app(DocumentService::class);

        // Dokumen Berlaku di ujung siklus: Edisi 1, Revisi 4.
        $doc = $svc->createDraft($gl, $type, $gl->department, 'SOP Ujung Siklus');
        $doc->update([
            'status' => 'published', 'published_at' => now(), 'no_revisi' => 4, 'edisi' => '1',
            'doc_number_final' => $doc->doc_number,
        ]);

        $this->actingAs($sh)->post(route('documents.requestRevision', $doc))->assertRedirect();

        $new = \App\Models\Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();
        $this->assertSame(0, $new->no_revisi, 'revisi kembali 0');
        $this->assertSame('2', $new->edisi, 'edisi naik ke 2');

        // Approve → walau no_revisi 0, ini tetap REVISI: nomor diwariskan (bukan
        // nomor final baru) dan versi lama otomatis Tidak Berlaku.
        $new->update(['reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id]);
        $this->actingAs($gl)->post(route('documents.submit', $new))->assertRedirect();
        $this->actingAs($sh)->get(route('review.show', $new));
        $this->actingAs($sh)->post(route('review.store', $new), ['decision' => 'approve'])->assertRedirect();
        $this->actingAs($pimpinan)->post(route('approvals.store', $new), ['decision' => 'approve'])->assertRedirect();

        $new->refresh();
        $doc->refresh();
        $this->assertSame('published', $new->status, 'Edisi 2 Rev 0 Berlaku');
        $this->assertSame($doc->doc_number, $new->doc_number_final, 'nomor final diwariskan, bukan nomor baru');
        $this->assertSame('obsolete', $doc->status, 'versi lama (Edisi 1 Rev 4) otomatis Tidak Berlaku');
    }
}
