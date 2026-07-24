<?php

namespace Tests\Feature;

use App\Http\Controllers\DocumentController;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionClass;
use Tests\TestCase;

/**
 * Menjaga tata letak PDF yang berulang kali salah: kop JSA berulang TIAP halaman
 * (docs/JSA new.docx) sedangkan blok info+TTD hanya halaman 1; footer hanya
 * SEKALI di halaman terakhir; "Halaman X dari Y" di-stamp; orientasi ikut
 * schema; seluruh TTD bercap APPROVED saat dokumen Berlaku.
 *
 * Diperiksa dari PDF yang BENAR-BENAR dirender (bukan HTML-nya), dgn membaca
 * kembali teks di dalam stream PDF.
 */
class PrintLayoutTest extends TestCase
{
    use DatabaseTransactions;

    private const FOOTER = 'Dokumen elektronik ini merupakan dokumen tidak terkendali';

    /** Render PDF sungguhan (jalur sama dgn unduhan user, termasuk stamp halaman). */
    private function renderPdf(Document $document): array
    {
        $ref = new ReflectionClass(DocumentController::class);
        $render = $ref->getMethod('renderPdfDocument');
        $render->setAccessible(true);

        $dompdf = $render->invoke(app(DocumentController::class), $document);
        $canvas = $dompdf->getCanvas();

        return [
            $canvas->get_page_count(),
            $canvas->get_width(),
            $canvas->get_height(),
            $this->extractText($dompdf->output()),
        ];
    }

    /** y-terendah dari operator teks per halaman (koordinat PDF: origin kiri-bawah). */
    private function lowestTextPerPage(Document $document): array
    {
        $ref = new ReflectionClass(DocumentController::class);
        $render = $ref->getMethod('renderPdfDocument');
        $render->setAccessible(true);
        $raw = $render->invoke(app(DocumentController::class), $document)->output();

        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $sm);
        $lows = [];
        foreach ($sm[1] as $s) {
            $d = @gzuncompress($s);
            if ($d === false) {
                $d = @gzinflate($s);
            }
            $c = $d !== false ? $d : $s;
            if (! str_contains($c, 'BT') || ! preg_match_all('/[\d.]+\s+([\d.]+)\s+(?:Td|TD)/', $c, $m)) {
                continue;
            }
            $lows[] = min(array_map('floatval', $m[1]));
        }

        return $lows;
    }

    /**
     * Baca teks di dalam PDF: dekompres tiap stream, ambil literal "(...)", lalu
     * buang byte null (DomPDF menulis font tertanam sebagai UTF-16BE).
     */
    private function extractText(string $raw): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $streams);

        $text = '';
        foreach ($streams[1] as $stream) {
            $inflated = @gzuncompress($stream);
            if ($inflated === false) {
                $inflated = @gzinflate($stream);
            }
            $content = $inflated !== false ? $inflated : $stream;

            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $content, $literals);
            foreach ($literals[0] as $literal) {
                $literal = preg_replace('/\\\\([()\\\\])/', '$1', substr($literal, 1, -1));
                $text .= str_replace("\x00", '', $literal);
            }
        }

        return preg_replace('/\s+/', ' ', $text);
    }

    /** Dokumen JSA berisi banyak baris → dipastikan lebih dari satu halaman. */
    private function makeLongJsa(): Document
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Tata Letak'
        );

        $analisa = [];
        for ($i = 1; $i <= 6; $i++) {
            $analisa[] = [
                'langkah' => "Langkah {$i} ".str_repeat('lorem ipsum ', 4),
                'bahaya' => [[
                    'risiko' => "Risiko {$i} ".str_repeat('lorem ipsum dolor sit amet ', 6),
                    'pengendalian' => [str_repeat('lorem ipsum dolor sit amet ', 10)],
                ]],
            ];
        }

        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => $analisa]);
        // Disimpan dari widget tanggal (YYYY-MM-DD); dicetak gaya Indonesia.
        $doc->contents()->create(['section_key' => 'form_tgl_efektif', 'value_json' => '2026-09-06']);

        return $doc;
    }

    /**
     * WAJIB: pada JSA multi-halaman, isi tabel analisa HARUS mulai di halaman 1
     * (thead LANGSUNG disambut baris body — bukan halaman 1 kosong lalu isi di
     * halaman 2), dan header kolom (thead) berulang tiap halaman. Regresi lama:
     * head-block terlalu tinggi → baris pertama (atomik) di-bump ke halaman 2 →
     * halaman 1 tinggal thead & DomPDF berhenti mengulang thead. Diuji pada kondisi
     * TERBERAT: dokumen sudah Berlaku (sel stempel APPROVED menambah tinggi head-block).
     */
    public function test_jsa_body_starts_on_first_page_and_header_repeats(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Mulai Hal 1'
        );
        // Konten realistis (berspasi → wrap normal) & cukup banyak → multi-halaman.
        // Prefix "Tahap"/"Risiko" agar bisa dibedakan dari header "Uraian Langkah".
        $lang = fn (int $n) => str_repeat('kata kerja lorem ipsum dolor ', $n);
        $analisa = [];
        for ($i = 1; $i <= 12; $i++) {
            $bahaya = [];
            for ($b = 1; $b <= 2; $b++) {
                $bahaya[] = ['risiko' => 'Risiko '.$lang(3), 'pengendalian' => [$lang(4), $lang(3)]];
            }
            $analisa[] = ['langkah' => "Tahap {$i} ".$lang(5), 'bahaya' => $bahaya];
        }
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => $analisa]);
        $doc->update([
            'status' => 'published', 'published_at' => now(),
            'reviewer_id' => User::where('nrp', 'SH-0001')->value('id'),
            'approver_id' => User::where('nrp', 'PJO-0001')->value('id'),
        ]);
        $doc->refresh();

        [$pages, , , $text] = $this->renderPdf($doc);
        $this->assertGreaterThan(1, $pages, 'isi uji harus lebih dari satu halaman');
        $this->assertSame($pages, substr_count($text, 'Uraian Langkah Pekerjaan'),
            'header kolom (thead) HARUS berulang tiap halaman');

        // Halaman 1 harus memuat thead DAN baris body (langkah "Tahap 1"), dgn body
        // berada DI BAWAH thead → thead langsung disambut isi, halaman 1 tak kosong.
        $theadY = $bodyY = null;
        foreach ($this->positionedText($this->pageStreams($doc)[0]) as [, $y, , $t]) {
            if (str_contains($t, 'Uraian Langkah')) $theadY = $y;
            if (str_starts_with(trim($t), 'Tahap 1')) $bodyY = $bodyY === null ? $y : max($bodyY, $y);
        }
        $this->assertNotNull($theadY, 'thead tak ada di halaman 1');
        $this->assertNotNull($bodyY, 'baris body pertama (Tahap 1) tak ada di halaman 1 — halaman 1 kosong');
        $this->assertLessThan($theadY, $bodyY, 'body harus di BAWAH thead pada halaman 1');
    }

    /**
     * INTI mesin 2-fase: saat sebuah step melintang beberapa halaman, teks Langkah
     * ("N. ...") HARUS ditulis ULANG di baris pertama tiap halaman lanjutan (spt
     * docs/JSA new.docx: "1. Lipsum" muncul lagi di halaman 2). Diukur dari PDF nyata.
     */
    public function test_jsa_step_header_repeats_on_continuation(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'Header Lanjutan'
        );
        // SATU step panjang (banyak bahaya) → melintang beberapa halaman.
        $bahaya = [];
        for ($b = 1; $b <= 10; $b++) {
            $bahaya[] = ['risiko' => "Bahaya {$b} ".str_repeat('lorem ipsum ', 4),
                'pengendalian' => [str_repeat('kendali lorem ipsum ', 5), str_repeat('kendali lorem ipsum ', 4)]];
        }
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'LANGKAHSATU '.str_repeat('lorem ipsum ', 3), 'bahaya' => $bahaya],
        ]]);

        [$pages, , , $text] = $this->renderPdf($doc);
        $this->assertGreaterThan(1, $pages, 'isi uji harus lebih dari satu halaman');

        // Langkah step-1 ditulis ULANG di TIAP halaman yg dilintasinya → sebanyak
        // jumlah halaman (step tunggal mengisi semua halaman).
        $this->assertSame($pages, substr_count($text, 'LANGKAHSATU'),
            'teks Langkah harus diulang di tiap halaman lanjutan');

        // Bukti kuat: halaman 2 (lanjutan, bukan awal step) tetap memuat Langkah.
        $page2 = collect($this->positionedText($this->pageStreams($doc)[1]))
            ->contains(fn ($r) => str_contains($r[3], 'LANGKAHSATU'));
        $this->assertTrue($page2, 'halaman lanjutan (2) harus mengulang teks Langkah');
    }

    /**
     * TANPA revisi: kop JSA hanya di halaman 1 (body); halaman 2 dst. cukup HEADER
     * TABEL. Nomor halaman ikut di kop → di-stamp sekali ("1 dari N").
     */
    public function test_jsa_kop_only_on_first_page_table_header_repeats(): void
    {
        [$pages, $w, $h, $text] = $this->renderPdf($this->makeLongJsa());

        $this->assertGreaterThan(1, $pages, 'isi uji harus lebih dari satu halaman');
        $this->assertGreaterThan($h, $w, 'JSA harus landscape');

        // Kop (judul + baris Edisi) HANYA sekali, di halaman 1.
        $this->assertSame(1, substr_count($text, 'FORMULIR JOB SAFETY ANALYSIS'),
            'kop atas JSA hanya di HALAMAN 1 (#7)');
        $this->assertSame(1, substr_count($text, 'Edisi'), 'baris Edisi hanya di kop halaman 1');
        $this->assertSame(1, substr_count($text, 'No. Pekerjaan/JSA'),
            'blok info+TTD hanya di halaman 1');
        $this->assertSame(1, substr_count($text, self::FOOTER),
            'footer hanya sekali, di halaman terakhir');

        // Header tabel analisa TETAP berulang tiap halaman (pengganti kop).
        $this->assertSame($pages, substr_count($text, 'Uraian Langkah Pekerjaan'),
            'header tabel analisa berulang tiap halaman');

        // Nomor halaman di-stamp SEKALI di kop halaman 1.
        $this->assertSame(1, substr_count($text, "1 dari {$pages}"), 'halaman 1 bernomor "1 dari N"');
        $this->assertSame(0, substr_count($text, "2 dari {$pages}"), 'halaman 2 dst. tanpa nomor (tanpa kop)');

        // Kop halaman 1 memang memuat nomor halaman.
        $page1 = collect($this->positionedText($this->pageStreams($this->makeLongJsa())[0]))
            ->contains(fn ($r) => str_contains($r[3], 'dari '));
        $this->assertTrue($page1, 'nomor halaman berada di halaman 1');
    }

    /**
     * DENGAN lembar revisi (#1): kop JSA muncul DI HALAMAN LEMBAR REVISI *dan* di
     * halaman pertama body JSA (dua kali), tapi TIDAK di halaman analisa lanjutan.
     * Nomor halaman di-stamp di kedua kop ("1 dari N" + "2 dari N").
     */
    public function test_jsa_kop_on_revision_sheet_and_body_first_page(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Revisi Kop'
        );
        // Analisa cukup panjang agar body + analisa > 1 halaman (jadi ada hal. lanjutan).
        $analisa = [];
        for ($i = 1; $i <= 6; $i++) {
            $analisa[] = ['langkah' => "Langkah {$i} ".str_repeat('lorem ipsum ', 4), 'bahaya' => [[
                'risiko' => "Risiko {$i} ".str_repeat('lorem ipsum dolor sit amet ', 6),
                'pengendalian' => [str_repeat('lorem ipsum dolor sit amet ', 10)],
            ]]];
        }
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => $analisa]);
        $doc->contents()->create(['section_key' => 'catatan_revisi', 'value_json' => [
            ['no_rev' => 1, 'tanggal' => '2026-07-21', 'halaman' => '1', 'catatan' => 'Penambahan lokasi'],
        ]]);

        [$pages, , , $text] = $this->renderPdf($doc);

        $this->assertGreaterThan(2, $pages, 'uji: lembar revisi + body + lanjutan > 2 halaman');
        // Kop muncul DUA kali (lembar revisi + halaman pertama body); tidak di lanjutan.
        $this->assertSame(2, substr_count($text, 'FORMULIR JOB SAFETY ANALYSIS'),
            'kop di lembar revisi (hal.1) + halaman pertama body (hal.2)');
        // "Penambahan lokasi" = isi baris lembar revisi → hanya di lembar revisi.
        $this->assertSame(1, substr_count($text, 'Penambahan lokasi'), 'lembar revisi tampil sekali');
        // Nomor halaman di KEDUA kop.
        $this->assertSame(1, substr_count($text, "1 dari {$pages}"), 'kop lembar revisi bernomor 1');
        $this->assertSame(1, substr_count($text, "2 dari {$pages}"), 'kop body bernomor 2');
        $this->assertSame(0, substr_count($text, "3 dari {$pages}"), 'halaman analisa lanjutan tanpa nomor');
    }

    /**
     * ISI HALAMAN PENUH (#4): sebuah Uraian Langkah baru harus MULAI di halaman yang
     * masih memuat langkah sebelumnya (halaman terisi ke bawah), bukan dilempar utuh
     * ke halaman baru. Mesin v10 (probe datar) melempar → halaman 1 hanya Langkah 1.
     */
    public function test_jsa_fills_page_before_starting_new_step(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Isi Penuh'
        );
        $L = 'lorem ipsum dolor sit amet ';
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            // Langkah 1: pendek (± setengah halaman).
            ['langkah' => 'LANGKAHSATU '.str_repeat($L, 3), 'bahaya' => [[
                'risiko' => 'R1 '.str_repeat($L, 4),
                'pengendalian' => [str_repeat($L, 3), str_repeat($L, 3), str_repeat($L, 3)],
            ]]],
            // Langkah 2: PANJANG → melimpah ke halaman 2, tapi awalnya HARUS muat di hal.1.
            ['langkah' => 'LANGKAHDUA '.str_repeat($L, 3), 'bahaya' => [[
                'risiko' => 'R2 '.str_repeat($L, 4),
                'pengendalian' => array_fill(0, 8, str_repeat($L, 4)),
            ]]],
        ]]);

        [$pages] = $this->renderPdf($doc);
        $this->assertGreaterThan(1, $pages, 'uji harus lebih dari satu halaman');

        // Teks halaman 1 memuat KEDUA langkah → Langkah 2 mulai mengisi hal.1 (bukan dilempar).
        $page1 = collect($this->positionedText($this->pageStreams($doc)[0]))
            ->map(fn ($r) => $r[3])->implode(' ');
        $this->assertStringContainsString('LANGKAHSATU', $page1, 'Langkah 1 di halaman 1');
        $this->assertStringContainsString('LANGKAHDUA', $page1,
            'Langkah 2 harus MULAI di halaman 1 (halaman terisi penuh, bukan dilempar utuh)');
    }

    /**
     * HANGING INDENT (#7): baris ke-2 dst. sebuah teks HARUS sejajar dgn baris
     * pertamanya. Anchor pengukuran 1pt dulu diletakkan di DEPAN sel sehingga
     * lebarnya (±2.9pt) menggeser BARIS PERTAMA ke kanan — kini anchor di AKHIR.
     * Diperiksa utk JSA (Tindakan Pengendalian) & SOP (deskripsi Aktivitas).
     */
    public function test_wrapped_lines_align_with_first_line(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();

        // Awal x tiap BARIS (min x per baris) untuk teks yang memuat $token.
        $lineStarts = function ($doc, string $token): array {
            $lines = [];
            foreach ($this->pageStreams($doc) as $stream) {
                foreach ($this->positionedText($stream) as [$x, $y, , $text]) {
                    if (! str_contains($text, $token)) {
                        continue;
                    }
                    $k = (string) round($y, 1);
                    $lines[$k] = isset($lines[$k]) ? min($lines[$k], round($x, 1)) : round($x, 1);
                }
            }

            return array_values($lines);
        };

        // --- JSA: satu tindakan pengendalian yang panjang (membungkus banyak baris).
        $jsa = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'Indentasi Kendali'
        );
        $jsa->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'Langkah', 'bahaya' => [
                ['risiko' => 'Risiko', 'pengendalian' => [trim(str_repeat('KENDALIX ', 40))]],
            ]],
        ]]);

        $xs = $lineStarts($jsa, 'KENDALIX');
        $this->assertGreaterThan(2, count($xs), 'teks kendali harus membungkus beberapa baris');
        $this->assertCount(1, array_unique($xs),
            'semua baris kendali JSA harus mulai di x sama; dapat: '.implode(',', array_unique($xs)));

        // --- SOP: deskripsi aktivitas yang panjang (template & perbaikan yang sama).
        $sop = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'Indentasi Aktivitas'
        );
        $sop->contents()->create(['section_key' => 'aktivitas', 'value_json' => [
            ['sub_judul' => 'Sub', 'deskripsi' => trim(str_repeat('AKTIVITASX ', 30)), 'pic' => 'ICT'],
        ]]);

        $xs = $lineStarts($sop, 'AKTIVITASX');
        $this->assertGreaterThan(2, count($xs), 'deskripsi aktivitas harus membungkus beberapa baris');
        $this->assertCount(1, array_unique($xs),
            'semua baris deskripsi aktivitas harus mulai di x sama; dapat: '.implode(',', array_unique($xs)));
    }

    /**
     * Kop JSA: "No. Dokumen" = nomor FORMULIR SHE (hardcode, tetap), sedangkan
     * nomor dokumen kita muncul di baris "No. Pekerjaan/JSA" — dua hal BERBEDA.
     */
    public function test_jsa_kop_form_number_vs_job_number(): void
    {
        $doc = $this->makeLongJsa();
        [, , , $text] = $this->renderPdf($doc);

        $this->assertStringContainsString('PPA-ADRO-F-SHE-03B', $text, 'No. Dokumen kop = nomor formulir SHE (hardcode)');
        $this->assertStringContainsString('No. Pekerjaan/JSA', $text);
        $this->assertStringContainsString($doc->displayNumber(), $text, 'nomor dokumen kita di baris No. Pekerjaan/JSA');
        $this->assertStringContainsString('6 September 2026', $text, 'Tgl Efektif dari widget tanggal');
        $this->assertStringNotContainsString('6 September 2022', $text, 'default lama sudah dibuang');
    }

    /** Saat Berlaku, SELURUH TTD (Dibuat/Ditinjau/Disetujui) bercap APPROVED. */
    public function test_published_document_stamps_every_signature(): void
    {
        $doc = $this->makeLongJsa();
        $doc->update([
            'status' => 'published',
            'published_at' => now(),
            'reviewer_id' => User::where('nrp', 'SH-0001')->value('id'),
            'approver_id' => User::where('nrp', 'PJO-0001')->value('id'),
        ]);

        [, , , $text] = $this->renderPdf($doc);

        $this->assertSame(3, substr_count($text, 'APPROVED'), 'ketiga TTD bercap APPROVED');
    }

    /** Lembar CATATAN REVISI: tampil di depan PDF hanya utk dokumen hasil revisi. */
    public function test_catatan_revisi_sheet_only_on_revised_documents(): void
    {
        $doc = $this->makeLongJsa();

        [, , , $text] = $this->renderPdf($doc);
        $this->assertStringNotContainsString('CATATAN REVISI', $text, 'dokumen non-revisi tanpa lembar catatan');

        $doc->contents()->create(['section_key' => 'catatan_revisi', 'value_json' => [
            ['no_rev' => 1, 'tanggal' => '2026-07-18', 'halaman' => '1-2', 'catatan' => 'Perubahan detail aktivitas'],
        ]]);
        $doc->refresh();

        [, , , $text] = $this->renderPdf($doc);
        // 2 = judul lembar + nama kolom terakhir (keduanya "CATATAN REVISI").
        $this->assertSame(2, substr_count($text, 'CATATAN REVISI'), 'lembar catatan revisi tampil sekali');
        $this->assertStringContainsString('18 JULI 2026', $text, 'tanggal berformat Indonesia');
        $this->assertStringContainsString('Perubahan detail aktivitas', $text);
    }

    /**
     * WAJIB (#1/#5A): tak ada teks yang melewati margin bawah 2cm (57pt).
     * Dulu aktivitas panjang terpotong di dasar halaman (tabel bersarang atomik).
     */
    public function test_no_text_crosses_bottom_margin(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();

        // SOP: aktivitas panjang berbilah banyak → multi-halaman.
        $bigDesc = implode("\n", array_map(fn ($k) => "{$k}. ".str_repeat('lorem ipsum dolor sit amet ', 12), range(1, 10)));
        $sop = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Margin');
        $sop->contents()->create(['section_key' => 'aktivitas', 'value_json' => [
            ['sub_judul' => 'Kegiatan A', 'deskripsi' => $bigDesc, 'pic' => 'ICT'],
            ['sub_judul' => 'Kegiatan B', 'deskripsi' => $bigDesc, 'pic' => 'ICT'],
        ]]);

        foreach ($this->lowestTextPerPage($sop) as $page => $y) {
            $this->assertGreaterThanOrEqual(55.0, $y, "SOP halaman ".($page + 1)." menembus margin bawah 2cm (y={$y})");
        }

        foreach ($this->lowestTextPerPage($this->makeLongJsa()) as $page => $y) {
            $this->assertGreaterThanOrEqual(55.0, $y, "JSA halaman ".($page + 1)." menembus margin bawah 2cm (y={$y})");
        }
    }

    /** Operator mentah tiap halaman (sudah didekompres). */
    private function pageStreams(Document $document): array
    {
        $ref = new ReflectionClass(DocumentController::class);
        $render = $ref->getMethod('renderPdfDocument');
        $render->setAccessible(true);
        $raw = $render->invoke(app(DocumentController::class), $document)->output();

        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $sm);
        $out = [];
        foreach ($sm[1] as $s) {
            $d = @gzuncompress($s);
            if ($d === false) {
                $d = @gzinflate($s);
            }
            $c = $d !== false ? $d : $s;
            if (str_contains($c, 'BT')) {
                $out[] = $c;
            }
        }

        return $out;
    }

    /** Teks + posisinya: [x, y, ukuran font, isi] per operator Td. */
    private function positionedText(string $stream): array
    {
        // DomPDF menulis "/F2 16.0 Tf" SESUDAH Td, di dalam segmen yang sama —
        // jadi ukuran font diambil dari segmen itu sendiri, bukan dilacak berurutan.
        preg_match_all('/([\d.]+)\s+([\d.]+)\s+(?:Td|TD)\s*(.*?)(?:TJ|Tj)/s', $stream, $m, PREG_SET_ORDER);

        $size = 0.0;
        $rows = [];
        foreach ($m as $seg) {
            if (preg_match('#/[A-Za-z0-9_+.-]+\s+([\d.]+)\s+Tf#', $seg[3], $tf)) {
                $size = (float) $tf[1];
            }
            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $seg[3], $lit);
            $t = '';
            foreach ($lit[0] as $l) {
                $t .= str_replace("\x00", '', preg_replace('/\\\\([()\\\\])/', '$1', substr($l, 1, -1)));
            }
            if (trim($t) !== '') {
                $rows[] = [(float) $seg[1], (float) $seg[2], $size, $t];
            }
        }

        return $rows;
    }

    /**
     * JENIS & JUDUL dokumen di kop HARUS besar (14pt / 12pt), bukan 8pt.
     * Pernah regresi diam-diam: `.kop-title` polos KALAH spesifisitas lawan
     * `table.kop td` (0,1,0 vs 0,1,2) → font-size tak pernah terpakai.
     */
    public function test_kop_title_font_is_enlarged(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sop = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'Judul Kop Besar');

        $found = [];
        foreach ($this->positionedText($this->pageStreams($sop)[0]) as [, , $size, $text]) {
            if (str_starts_with($text, 'STANDARD')) {
                $found['jenis'] = $size;
            }
            if (str_starts_with($text, 'JUDUL KOP')) {
                $found['judul'] = $size;
            }
        }

        $this->assertSame(14.0, $found['jenis'] ?? null, 'JENIS dokumen harus 14pt');
        $this->assertSame(12.0, $found['judul'] ?? null, 'JUDUL dokumen harus 12pt');

        foreach ($this->positionedText($this->pageStreams($this->makeLongJsa())[0]) as [, , $size, $text]) {
            if (str_starts_with($text, 'FORMULIR')) {
                // v11 (#7): kop dirampingkan 16→13pt agar ruang tabel bertambah.
                $this->assertSame(13.0, $size, 'judul FORMULIR JSA harus 13pt (kop ramping)');
            }
        }
    }

    /**
     * Nomor "5.1" harus SEBARIS dgn sub-judulnya. Baseline inline-block DomPDF
     * membuat nomor mengambang 2.7pt di atas teks; dikoreksi vertical-align.
     */
    public function test_activity_number_aligns_with_its_title(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $sop = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Sejajar');
        $sop->contents()->create(['section_key' => 'aktivitas', 'value_json' => [
            ['sub_judul' => 'Lipsum', 'deskripsi' => 'ada lagi revisi nya', 'pic' => 'ICT'],
        ]]);

        $numY = $titleY = null;
        foreach ($this->positionedText($this->pageStreams($sop)[0]) as [, $y, , $text]) {
            if ($text === '5.1') {
                $numY = $y;
            }
            if ($text === 'Lipsum') {
                $titleY = $y;
            }
        }

        $this->assertNotNull($numY, 'nomor 5.1 tak ditemukan');
        $this->assertNotNull($titleY, 'sub-judul tak ditemukan');
        $this->assertLessThanOrEqual(0.6, abs($numY - $titleY), "nomor & sub-judul tak sejajar (nomor y={$numY}, judul y={$titleY})");
    }

    /**
     * Teks JSA tak boleh MENEMBUS kolom sebelah. Kata sangat panjang tanpa spasi
     * dulu meluber karena DomPDF MENGABAIKAN `word-break` — hanya `overflow-wrap`
     * (nilai `anywhere`) yang memenggal di tengah kata.
     */
    public function test_jsa_text_never_crosses_column_borders(): void
    {
        $long = str_repeat('kmlklm', 12).str_repeat('m', 40);
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Luber');
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => $long, 'bahaya' => [
                ['risiko' => $long, 'pengendalian' => [$long, $long]],
                ['risiko' => $long, 'pengendalian' => [$long]],
            ]],
        ]]);

        // Batas kolom tabel analisa: 20/20/46/14 dari lebar isi (A4 landscape,
        // margin kiri/kanan 8pt).
        $w = 842 - 16;
        $edges = [8.0, 8 + $w * .20, 8 + $w * .40, 8 + $w * .86, 8 + $w];

        $fm = (new \Dompdf\Dompdf())->getFontMetrics();
        $font = $fm->getFont('Arial', 'normal');

        foreach ($this->pageStreams($doc) as $page => $stream) {
            foreach ($this->positionedText($stream) as [$x, , , $text]) {
                if (! str_contains($text, 'kml')) {
                    continue;
                }
                $right = $x + $fm->getTextWidth($text, $font, 8.0);
                foreach ($edges as $edge) {
                    if ($edge > $x + 2) {
                        $this->assertLessThanOrEqual($edge + 6, $right, 'teks menembus batas kolom di halaman '.($page + 1));

                        break;
                    }
                }
            }
        }
    }

    public function test_sop_is_portrait_with_single_footer(): void
    {
        $gl = User::where('nrp', 'GL-0001')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Tata Letak'
        );

        $long = [];
        for ($i = 1; $i <= 30; $i++) {
            $long[] = "Butir {$i} ".str_repeat('lorem ipsum dolor sit amet ', 10);
        }
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => $long]);

        [$pages, $w, $h, $text] = $this->renderPdf($doc);

        $this->assertGreaterThan(1, $pages, 'isi uji harus lebih dari satu halaman');
        $this->assertGreaterThan($w, $h, 'SOP harus portrait');
        $this->assertSame(1, substr_count($text, self::FOOTER), 'footer hanya sekali, di halaman terakhir');
        $this->assertSame($pages, substr_count($text, "dari {$pages}"), '"Halaman: X dari Y" di-stamp tiap halaman');
        $this->assertSame(1, substr_count($text, "Halaman: 1 dari {$pages}"), 'halaman pertama bernomor 1');
    }
}
