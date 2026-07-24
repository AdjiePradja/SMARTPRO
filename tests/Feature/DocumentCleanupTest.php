<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DocumentCleanupTest extends TestCase
{
    use DatabaseTransactions;

    public function test_generate_final_fills_gap(): void
    {
        $dept = Department::create(['code' => 'TST'.random_int(100, 999), 'name' => 'Test Dept']);
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $svc = app(DocumentNumberService::class);

        // Belum ada final → mulai 01.
        $this->assertStringEndsWith('-01', $svc->generateFinal($type, $dept));

        // Final 01 & 03 terpakai → celah di 02.
        $a = app(DocumentService::class)->createDraft($gl, $type, $dept, 'A');
        $a->update(['doc_number_final' => "PPA-ADRO-SOP-{$dept->code}-01", 'status' => 'published']);
        $c = app(DocumentService::class)->createDraft($gl, $type, $dept, 'C');
        $c->update(['doc_number_final' => "PPA-ADRO-SOP-{$dept->code}-03", 'status' => 'published']);

        $this->assertStringEndsWith('-02', $svc->generateFinal($type, $dept), 'nomor final mengisi celah kosong');
    }

    /**
     * Nomor sementara tak boleh bentrok. Dulu memakai count()+1, sehingga begitu
     * ada dokumen dihapus (tak ikut terhitung) draft baru memperoleh nomor yang
     * SAMA dengan dokumen yang masih ada.
     */
    public function test_temp_number_never_collides_after_delete(): void
    {
        $dept = Department::create(['code' => 'TMP'.random_int(100, 999), 'name' => 'Temp Dept']);
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $svc = app(DocumentService::class);

        $a = $svc->createDraft($gl, $type, $dept, 'A');   // -01
        $b = $svc->createDraft($gl, $type, $dept, 'B');   // -02
        $c = $svc->createDraft($gl, $type, $dept, 'C');   // -03

        $this->assertStringEndsWith('-03', $c->doc_number);

        // Hapus yang di tengah → nomornya TIDAK boleh dipakai ulang selama
        // dokumen lain masih memakai -03.
        $b->delete();

        $d = $svc->createDraft($gl, $type, $dept, 'D');
        $this->assertStringEndsWith('-04', $d->doc_number, 'nomor baru tak boleh bentrok dgn dokumen yang ada');
        $this->assertSame(
            1,
            Document::withTrashed()->where('department_id', $dept->id)->where('doc_number', $d->doc_number)->count(),
            'nomor dokumen harus unik'
        );
    }

    public function test_published_can_be_made_obsolete_then_deleted(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail(); // yang berhak nonaktifkan & hapus (SH/DH/PJO)
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Cleanup');
        $doc->update(['status' => 'published', 'published_at' => now()]);

        // Jadikan Tidak Berlaku (oleh SH)
        $this->actingAs($sh)->post(route('documents.makeObsolete', $doc))->assertRedirect();
        $this->assertSame('obsolete', $doc->refresh()->status);

        // Hapus dari halaman Dokumen Tidak Berlaku
        $this->actingAs($sh)->delete(route('documents.destroy', $doc))->assertRedirect();
        $this->assertNull(Document::find($doc->id), 'dokumen obsolete terhapus');
    }

    public function test_make_obsolete_rejects_non_published(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Draft');

        $this->actingAs($sh)->post(route('documents.makeObsolete', $doc))->assertStatus(422);
    }
}
