<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JsaDocumentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_jsa_type_is_active_and_landscape(): void
    {
        $jsa = DocumentType::where('code', 'JSA')->firstOrFail();
        $this->assertTrue((bool) $jsa->is_active);
        $this->assertSame('landscape', $jsa->schema_json['orientation'] ?? null);
        $this->assertSame('documents.print.render-jsa', $jsa->schema_json['print_view'] ?? null);
    }

    public function test_nested_analisa_is_saved_and_cleaned(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Perbaikan Tower Radio');
        $doc->update(['current_step' => 2]);

        $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 2,
            'action' => 'save',
            'sections' => [
                'analisa' => [
                    [
                        'langkah' => 'Persiapan alat',
                        'bahaya' => [
                            ['risiko' => 'Terjatuh', 'pengendalian' => ['Body harness', '', 'Lifeline']],
                            ['risiko' => '', 'pengendalian' => ['']], // kosong → dibuang
                        ],
                    ],
                    ['langkah' => '', 'bahaya' => [['risiko' => '', 'pengendalian' => ['']]]], // kosong → dibuang
                ],
            ],
        ])->assertRedirect();

        $analisa = $doc->refresh()->contentMap()['analisa'] ?? null;

        $this->assertIsArray($analisa);
        $this->assertCount(1, $analisa, 'langkah kosong dibuang');
        $this->assertSame('Persiapan alat', $analisa[0]['langkah']);
        $this->assertCount(1, $analisa[0]['bahaya'], 'bahaya kosong dibuang');
        $this->assertSame(['Body harness', 'Lifeline'], $analisa[0]['bahaya'][0]['pengendalian'], 'pengendalian kosong dibuang');
    }

    public function test_jsa_pdf_renders(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Uji JSA PDF');
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'Langkah A', 'bahaya' => [['risiko' => 'Risiko A', 'pengendalian' => ['Kendali A']]]],
        ]]);

        $this->actingAs($gl)->get(route('documents.pdf', $doc))->assertOk();
    }

    /** #2: peninjau menandai ✓/✗ per pengendalian → tersimpan (kosong dibuang) & tampil di halaman tinjau. */
    public function test_jsa_review_checklist_saved_and_loaded(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sh = User::where('nrp', 'SH-0001')->firstOrFail();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'JSA Checklist');
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L A', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K1', 'K2']]]],
            ['langkah' => 'L B', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K3']]]],
        ]]);
        $doc->update([
            'reviewer_id' => $sh->id,
            'approver_id' => User::where('nrp', 'PJO-0001')->firstOrFail()->id,
            'status' => 'in_review',
        ]);

        // Peninjau setujui + tandai: P0=check, P1=cross, sisanya kosong.
        $this->actingAs($sh)->post(route('review.store', $doc), [
            'decision' => 'approve',
            'checklist' => ['L0-B0-P0' => 'check', 'L0-B0-P1' => 'cross', 'L1-B0-P0' => ''],
        ])->assertRedirect();

        // Hanya check/cross tersimpan; string kosong DIBUANG.
        $this->assertSame(
            ['L0-B0-P0' => 'check', 'L0-B0-P1' => 'cross'],
            $doc->refresh()->contentMap()['jsa_checklist'] ?? null,
            'hanya tanda terpilih disimpan'
        );

        // Halaman tinjau memuat kontrol checklist + nilai tersimpan (x-data).
        $doc->update(['status' => 'in_review']);
        $html = $this->actingAs($sh)->get(route('review.show', $doc))->assertOk()->getContent();
        $this->assertStringContainsString('Centang semua', $html, 'toolbar checklist tampil');
        $this->assertStringContainsString("set('L0-B0-P0', 'check')", $html, 'tombol ✓ per pengendalian ada');
        // x-data memuat tanda tersimpan: Js::from → marks: JSON.parse('{...}').
        // Decode payload-nya (kutip di-escape jadi \\u0022) lalu bandingkan nilai.
        preg_match("/marks: JSON\\.parse\\(.(.+?).\\),/", $html, $mm);
        $loaded = json_decode(str_replace(chr(92).'u0022', '"', $mm[1] ?? '{}'), true);
        $this->assertSame('check', $loaded['L0-B0-P0'] ?? null, 'tanda check dimuat ke UI');
        $this->assertSame('cross', $loaded['L0-B0-P1'] ?? null, 'tanda cross dimuat ke UI');
    }
}
