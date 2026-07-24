<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Fase 3: draft revisi Tipe B mendapat langkah wizard ekstra "Log Revisi"
 * (form lembar CATATAN REVISI); Simpan/Kirim pindah ke langkah itu; barisnya
 * terakumulasi lintas revisi dan tercetak di halaman depan PDF.
 */
class RevisionLogTest extends TestCase
{
    use DatabaseTransactions;

    /** Buat dokumen Berlaku lalu ajukan revisi (SH) → kembalikan [lama, draft baru]. */
    private function makeRevisionDraft(): array
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Log Revisi');
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan awal']]);
        $doc->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $doc->doc_number]);

        $this->actingAs($sh)->post(route('documents.requestRevision', $doc))->assertRedirect();
        $new = Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();

        return [$doc, $new];
    }

    public function test_revision_draft_gets_extra_log_step(): void
    {
        [, $new] = $this->makeRevisionDraft();
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();

        $this->assertTrue($new->isRevisionDraft(), 'draft revisi menunjuk dokumen asalnya');

        // Wizard menampilkan langkah ekstra "Log Revisi".
        $this->actingAs($gl)->get(route('documents.edit', $new))
            ->assertOk()
            ->assertSee('Log Revisi');

        // Langkah 2 (terakhir schema) BUKAN lagi langkah akhir → tombol Kirim
        // tidak ada di sana; pindah ke langkah 3.
        $new->update(['current_step' => 2]);
        $html = $this->actingAs($gl)->get(route('documents.edit', $new))->getContent();
        $this->assertStringNotContainsString('value="submit"', $html, 'Kirim tidak di langkah 2 saat revisi');
        $this->assertStringContainsString('Langkah Berikutnya', $html);

        $new->update(['current_step' => 3]);
        $html = $this->actingAs($gl)->get(route('documents.edit', $new))->getContent();
        $this->assertStringContainsString('value="submit"', $html, 'Kirim ada di langkah 3 (Log Revisi)');
        $this->assertStringContainsString('Catatan Perubahan', $html);
    }

    public function test_log_step_saves_rows_and_manual_edisi_override(): void
    {
        [, $new] = $this->makeRevisionDraft();
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $new->update(['current_step' => 3]);

        $this->actingAs($gl)->post(route('documents.saveStep', $new), [
            'step' => 3,
            'action' => 'save',
            'edisi' => 2,          // override manual
            'no_revisi' => 3,      // override manual
            'sections' => ['catatan_revisi' => [
                ['no_rev' => '', 'tanggal' => '2026-07-19', 'halaman' => '1-2', 'catatan' => 'Perubahan detail aktivitas'],
                ['no_rev' => '', 'tanggal' => '', 'halaman' => '', 'catatan' => ''],   // kosong → dibuang
            ]],
        ])->assertRedirect();

        $new->refresh();
        $this->assertSame('2', $new->edisi, 'edisi bisa dioverride manual');
        $this->assertSame(3, $new->no_revisi, 'no_revisi bisa dioverride manual');

        $rows = $new->contentMap()['catatan_revisi'];
        $this->assertCount(1, $rows, 'baris kosong dibuang');
        $this->assertSame('Perubahan detail aktivitas', $rows[0]['catatan']);
        $this->assertSame(3, $rows[0]['no_rev'], 'baris baru diberi no_revisi dokumen ini');
    }

    /**
     * v3 rev: pengaju revisi (SH/DH/PJO) TIDAK dibawa ke form edit — draft revisi
     * milik GL & tampil di Status Dokumen-nya; dokumen Berlaku tidak lagi tampil
     * di Status Dokumen (pindah ke Dokumen Berlaku).
     */
    public function test_requester_not_redirected_to_edit_and_status_list_filtered(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Alur Revisi');
        $doc->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $doc->doc_number]);

        // Pengaju (SH) TIDAK diarahkan ke form edit draft revisi.
        $resp = $this->actingAs($sh)->post(route('documents.requestRevision', $doc));
        $resp->assertRedirect();
        $this->assertStringNotContainsString('/edit', $resp->headers->get('Location') ?? '', 'pengaju tidak dibawa ke form edit');

        // GL dinotifikasi draft revisinya.
        $this->assertTrue(
            $gl->notifications()->get()->contains(fn ($n) => str_contains($n->data['message'] ?? '', 'diajukan revisi')),
            'GL menerima notifikasi revisi'
        );

        // Status Dokumen GL: versi lama (sedang_direvisi) TIDAK tampil; draft revisi TAMPIL.
        $new = Document::where('revises_document_id', $doc->id)->firstOrFail();
        $this->actingAs($gl)->get(route('documents.index'))
            ->assertOk()
            ->assertViewHas('documents', function ($docs) use ($doc, $new) {
                $ids = collect($docs->items())->pluck('id');

                return $ids->contains($new->id)      // draft revisi muncul kembali
                    && ! $ids->contains($doc->id);   // versi Berlaku/sedang_direvisi tersembunyi
            });
    }

    /** v3 rev3 #7: draft revisi Tipe B tampil di menu "Dokumen Revisi" GL. */
    public function test_revision_draft_appears_in_dokumen_revisi_menu(): void
    {
        [, $new] = $this->makeRevisionDraft();
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();

        $this->actingAs($gl)->get(route('documents.revisi'))
            ->assertOk()
            ->assertViewHas('documents', fn ($docs) => collect($docs->items())->pluck('id')->contains($new->id));
    }

    public function test_rows_accumulate_across_revisions(): void
    {
        [, $rev1] = $this->makeRevisionDraft();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();

        // Revisi 1 terbit dengan satu baris catatan.
        app(DocumentService::class)->saveSection($rev1, 'catatan_revisi', [
            ['no_rev' => 1, 'tanggal' => '2026-07-18', 'halaman' => '1', 'catatan' => 'Perubahan pertama'],
        ]);
        $rev1->update(['status' => 'published', 'published_at' => now()]);

        // Ajukan revisi lagi → draft revisi 2 MEWARISI baris revisi 1.
        $this->actingAs($sh)->post(route('documents.requestRevision', $rev1))->assertRedirect();
        $rev2 = Document::where('revises_document_id', $rev1->id)->firstOrFail();

        $rows = $rev2->contentMap()['catatan_revisi'];
        $this->assertCount(1, $rows, 'baris revisi terdahulu terbawa (terakumulasi)');
        $this->assertSame('Perubahan pertama', $rows[0]['catatan']);
        $this->assertSame(2, $rev2->no_revisi, 'revisi naik ke 2');
    }
}
