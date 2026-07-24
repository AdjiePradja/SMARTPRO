<?php

namespace Tests\Unit;

use App\Services\ActivityPrintLayout;
use PHPUnit\Framework\TestCase;

/**
 * Logika paginasi tabel AKTIVITAS (SOP/SP/IK) — murni, tanpa render.
 *
 * Mengunci perbaikan bug: saat satu aktivitas TERPOTONG antar halaman, (a) border
 * tabel harus TERTUTUP di batas halaman (baris terakhir tiap halaman TIDAK boleh
 * memakai kelas yang membuang border-bottom) dan (b) sel PIC tak boleh rowspan
 * melintasi halaman — harus DIULANG dgn rowspan baru di halaman lanjutan.
 */
class ActivityPrintLayoutTest extends TestCase
{
    /** Grup 0: judul + 3 paragraf (4 baris). Grup 1: judul + 1 paragraf (2 baris). */
    private function sampleGroups(): array
    {
        return [
            ['sub_judul' => 'Persiapan', 'deskripsi' => "P1\nP2\nP3", 'pic' => 'ICT'],
            ['sub_judul' => 'Penutup', 'deskripsi' => 'Q1', 'pic' => 'Plant'],
        ];
    }

    public function test_flatten_makes_head_row_plus_one_row_per_paragraph(): void
    {
        $rows = ActivityPrintLayout::flatten($this->sampleGroups(), '5.');

        $this->assertCount(6, $rows);
        $this->assertTrue($rows[0]['isHead']);
        $this->assertSame('5.1', $rows[0]['number']);
        $this->assertSame('Persiapan', $rows[0]['text']);
        $this->assertFalse($rows[1]['isHead']);
        $this->assertSame('P1', $rows[1]['text']);
        // PIC dibawa di SETIAP baris (dipakai saat diulang di halaman lanjutan).
        $this->assertSame('ICT', $rows[3]['pic']);
        $this->assertTrue($rows[4]['isHead']);
        $this->assertSame('5.2', $rows[4]['number']);
    }

    public function test_single_page_pic_rowspan_covers_whole_group(): void
    {
        $rows = ActivityPrintLayout::plan(ActivityPrintLayout::flatten($this->sampleGroups(), '5.'));

        $this->assertTrue($rows[0]['showPic']);
        $this->assertSame(4, $rows[0]['picRowspan'], 'PIC grup-1 menutupi 4 baris');
        $this->assertFalse($rows[1]['showPic']);
        $this->assertTrue($rows[4]['showPic']);
        $this->assertSame(2, $rows[4]['picRowspan'], 'PIC grup-2 menutupi 2 baris');

        // Kelas border satu segmen penuh: atas → tengah → bawah.
        $this->assertSame('mrg-top', $rows[0]['mrg']);
        $this->assertSame('mrg-mid', $rows[1]['mrg']);
        $this->assertSame('mrg-bot', $rows[3]['mrg']);
    }

    /** Grup terpotong halaman: border tertutup di kedua sisi & PIC diulang. */
    public function test_page_break_mid_group_closes_borders_and_repeats_pic(): void
    {
        // Paksa halaman baru di baris 2 (tengah grup-1: baris 0-1 hal.1, baris 2-3 hal.2).
        $rows = ActivityPrintLayout::plan(ActivityPrintLayout::flatten($this->sampleGroups(), '5.'), [2]);

        $this->assertTrue($rows[2]['pageBreakBefore']);
        $this->assertSame(1, $rows[1]['pageNo']);
        $this->assertSame(2, $rows[2]['pageNo']);

        // (a) BORDER TERTUTUP: baris terakhir di hal.1 pakai 'mrg-bot' (border-bottom
        //     tetap ada) dan baris pertama hal.2 pakai 'mrg-top' (border-top tetap ada).
        $this->assertSame('mrg-bot', $rows[1]['mrg'], 'tepi bawah halaman 1 harus tertutup');
        $this->assertSame('mrg-top', $rows[2]['mrg'], 'tepi atas halaman 2 harus tertutup');

        // (b) PIC diulang di halaman lanjutan dgn rowspan BARU (tak lintas halaman).
        $this->assertTrue($rows[0]['showPic']);
        $this->assertSame(2, $rows[0]['picRowspan'], 'PIC hal.1 hanya span 2 baris');
        $this->assertTrue($rows[2]['showPic'], 'PIC harus muncul lagi di halaman lanjutan');
        $this->assertSame(2, $rows[2]['picRowspan'], 'PIC hal.2 span 2 baris');
        $this->assertSame('ICT', $rows[2]['pic']);
    }

    /** Invarian umum: tak ada rowspan PIC yang melintasi batas halaman. */
    public function test_no_pic_rowspan_ever_crosses_a_page_boundary(): void
    {
        foreach ([[1], [2], [3], [4], [1, 3], [2, 5]] as $starts) {
            $rows = ActivityPrintLayout::plan(ActivityPrintLayout::flatten($this->sampleGroups(), '5.'), $starts);

            foreach ($rows as $i => $r) {
                if (! $r['showPic']) {
                    continue;
                }
                for ($j = $i; $j < $i + $r['picRowspan']; $j++) {
                    $this->assertSame(
                        $r['pageNo'], $rows[$j]['pageNo'],
                        'rowspan PIC tidak boleh melintasi halaman (starts: '.implode(',', $starts).')'
                    );
                }
            }
        }
    }

    /** Segmen satu baris = border penuh (tak ada kelas yang membuang border). */
    public function test_single_row_segment_keeps_full_borders(): void
    {
        $rows = ActivityPrintLayout::plan(
            ActivityPrintLayout::flatten([['sub_judul' => 'Tunggal', 'deskripsi' => '', 'pic' => 'ICT']], '1.')
        );

        $this->assertCount(1, $rows);
        $this->assertSame('', $rows[0]['mrg'], 'baris tunggal memakai border penuh');
        $this->assertSame(1, $rows[0]['picRowspan']);
    }
}
