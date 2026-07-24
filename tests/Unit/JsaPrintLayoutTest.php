<?php

namespace Tests\Unit;

use App\Services\JsaPrintLayout;
use PHPUnit\Framework\TestCase;

/**
 * Logika paginasi JSA (murni, tanpa render). Mengunci model ROWSPAN: sel Langkah
 * span seluruh bahaya+pengendalian-nya, sel Bahaya span seluruh pengendalian-nya,
 * dan saat pindah halaman keduanya DIULANG dgn rowspan baru (di-scope per halaman).
 */
class JsaPrintLayoutTest extends TestCase
{
    private function sampleAnalisa(): array
    {
        // Step 1: 2 bahaya × 2 pengendalian = 4 baris. Step 2: 1 bahaya × 1 = 1 baris.
        return [
            ['langkah' => 'Langkah Satu', 'bahaya' => [
                ['risiko' => 'B1', 'pengendalian' => ['p1', 'p2']],
                ['risiko' => 'B2', 'pengendalian' => ['p3', 'p4']],
            ]],
            ['langkah' => 'Langkah Dua', 'bahaya' => [
                ['risiko' => 'B3', 'pengendalian' => ['p5']],
            ]],
        ];
    }

    public function test_flatten_produces_one_row_per_pengendalian_with_numbers(): void
    {
        $rows = JsaPrintLayout::flatten($this->sampleAnalisa());

        $this->assertCount(5, $rows);
        $this->assertSame('1.', $rows[0]['stepNo']);
        $this->assertSame('1.1', $rows[0]['bahayaNo']);
        $this->assertSame('1.1.1', $rows[0]['kendaliNo']);
        $this->assertSame('1.2.2', $rows[3]['kendaliNo']);
        $this->assertSame('2.1.1', $rows[4]['kendaliNo']);
        // Nomor Bahaya disimpan di SETIAP baris (dipakai saat diulang antar-halaman).
        $this->assertSame('1.1', $rows[1]['bahayaNo']);
        $this->assertSame('1.2', $rows[2]['bahayaNo']);
    }

    public function test_single_page_rowspans_cover_full_groups(): void
    {
        $rows = JsaPrintLayout::plan(JsaPrintLayout::flatten($this->sampleAnalisa()));

        // Langkah tampil sekali per step; rowspan menutupi SELURUH baris step.
        $this->assertTrue($rows[0]['showLangkah']);
        $this->assertSame(4, $rows[0]['stepRowspan'], 'step-1 span 4 baris');
        $this->assertFalse($rows[1]['showLangkah']);
        $this->assertFalse($rows[3]['showLangkah']);
        $this->assertTrue($rows[4]['showLangkah']);
        $this->assertSame(1, $rows[4]['stepRowspan'], 'step-2 span 1 baris');

        // Bahaya tampil sekali per bahaya; rowspan menutupi pengendalian-nya (cover benar #8).
        $this->assertTrue($rows[0]['showBahaya']);
        $this->assertSame(2, $rows[0]['bahayaRowspan'], 'bahaya 1.1 span 1.1.1 & 1.1.2');
        $this->assertFalse($rows[1]['showBahaya']);
        $this->assertTrue($rows[2]['showBahaya']);
        $this->assertSame(2, $rows[2]['bahayaRowspan'], 'bahaya 1.2 span 1.2.1 & 1.2.2');
    }

    public function test_page_break_mid_step_repeats_headers_scoped_per_page(): void
    {
        // Paksa halaman baru di baris 2 (tengah step-1, tepat di batas bahaya 1.1/1.2).
        $rows = JsaPrintLayout::plan(JsaPrintLayout::flatten($this->sampleAnalisa()), [2]);

        // Baris 2 memulai halaman & MENGULANG langkah step-1 dgn rowspan baru.
        $this->assertTrue($rows[2]['pageBreakBefore']);
        $this->assertSame(2, $rows[2]['pageNo']);
        $this->assertTrue($rows[2]['showLangkah'], 'langkah harus diulang di awal halaman lanjutan');

        // Rowspan DI-SCOPE per halaman: hal.1 step-1 span 2 (baris 0-1), hal.2 span 2 (baris 2-3).
        $this->assertSame(2, $rows[0]['stepRowspan'], 'step-1 di hal.1 hanya span 2 baris');
        $this->assertSame(2, $rows[2]['stepRowspan'], 'step-1 di hal.2 span 2 baris');

        // Bahaya 1.2 (baris 2) mulai di halaman 2 → tampil dgn rowspan-nya sendiri.
        $this->assertTrue($rows[2]['showBahaya']);
        $this->assertSame(2, $rows[2]['bahayaRowspan']);
    }
}
