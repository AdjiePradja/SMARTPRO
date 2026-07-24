<?php

namespace App\Services;

/**
 * Tata letak tabel "AKTIVITAS DAN TANGGUNG JAWAB" (SOP/SP/IK) untuk cetak PDF —
 * mesin paginasi 2-fase, sekerabat dgn {@see JsaPrintLayout}.
 *
 * MODEL: satu grup aktivitas = 1 baris JUDUL (nomor + sub-judul) + N baris
 * PARAGRAF deskripsi. Deskripsi sengaja dipecah per paragraf agar aktivitas
 * panjang MENGALIR antar halaman (tak meluber margin bawah). Sel PIC digabung
 * (rowspan) melintasi grup → rata tengah atas-bawah.
 *
 * MASALAH DomPDF (sama spt JSA): sel rowspan yang MELINTASI batas halaman RUSAK —
 * PIC hilang di halaman lanjutan — dan kelas border "sel gabungan" (mrg-top/mid)
 * membuang border-bottom sehingga tabel MENGGANTUNG (border tak tertutup) di
 * dasar halaman. SOLUSI: rowspan & kelas border DI-SCOPE PER HALAMAN:
 *   - Segmen = baris-baris satu grup yang berada di halaman yang SAMA.
 *   - Baris terakhir tiap segmen memakai 'mrg-bot' (border-bottom TETAP ada)
 *     → tepi bawah halaman TERTUTUP.
 *   - Baris pertama segmen berikutnya memakai 'mrg-top' (border-top TETAP ada)
 *     → tepi atas halaman lanjutan TERTUTUP.
 *   - PIC diulang dgn rowspan baru tiap segmen → tak ada rowspan lintas halaman.
 *
 * Kelas MURNI (tanpa render) → mudah diuji & direview.
 */
class ActivityPrintLayout
{
    /**
     * Ratakan grup aktivitas → baris DATAR: 1 baris judul + 1 baris per paragraf
     * deskripsi. Posisi array == indeks (dipakai anchor pengukuran Fase 1).
     *
     * @param  array  $groups  isi section aktivitas (sub_judul, deskripsi, pic)
     * @param  string  $autoNumber  awalan nomor, mis. "5." → 5.1, 5.2, …
     * @return array<int, array<string, mixed>>
     */
    public static function flatten(array $groups, string $autoNumber = ''): array
    {
        $rows = [];

        foreach (array_values($groups) as $gi => $group) {
            if (! is_array($group)) {
                continue;
            }

            $pic = trim((string) ($group['pic'] ?? ''));
            $paras = array_values(array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($group['deskripsi'] ?? '')) ?: []),
                fn ($p) => $p !== ''
            ));

            // Baris 0 = nomor + sub-judul; sisanya paragraf deskripsi.
            $rows[] = [
                'groupIdx' => $gi,
                'isHead' => true,
                'number' => $autoNumber.($gi + 1),
                'text' => (string) ($group['sub_judul'] ?? ''),
                'pic' => $pic,
            ];

            foreach ($paras as $p) {
                $rows[] = [
                    'groupIdx' => $gi,
                    'isHead' => false,
                    'number' => '',
                    'text' => $p,
                    'pic' => $pic,
                ];
            }
        }

        return $rows;
    }

    /**
     * Susun render-rows dari baris datar + himpunan indeks awal-halaman
     * ($pageStarts hasil ukur Fase 1). Menetapkan pageNo/pageBreakBefore lalu —
     * DI-SCOPE PER HALAMAN — showPic/picRowspan & kelas border (mrg).
     *
     * Tanpa $pageStarts = satu-lintasan (preview layar / fallback): perilaku
     * sama seperti sebelumnya (satu segmen per grup).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function plan(array $rows, array $pageStarts = []): array
    {
        $starts = array_fill_keys(array_map('intval', $pageStarts), true);

        // Halaman tiap baris (page-break menaikkan nomor halaman).
        $page = 1;
        foreach ($rows as $i => &$r) {
            if (isset($starts[$i]) && $i > 0) {
                $page++;
            }
            $r['pageNo'] = $page;
            $r['pageBreakBefore'] = isset($starts[$i]) && $i > 0;
        }
        unset($r);

        $n = count($rows);
        foreach ($rows as $i => &$r) {
            $prev = $i > 0 ? $rows[$i - 1] : null;
            $next = $i + 1 < $n ? $rows[$i + 1] : null;

            // Segmen = grup sama DAN halaman sama.
            $sameAsPrev = $prev && $prev['groupIdx'] === $r['groupIdx'] && $prev['pageNo'] === $r['pageNo'];
            $sameAsNext = $next && $next['groupIdx'] === $r['groupIdx'] && $next['pageNo'] === $r['pageNo'];

            // PIC: satu sel rowspan per SEGMEN (diulang di halaman lanjutan).
            $r['showPic'] = ! $sameAsPrev;
            $r['picRowspan'] = $r['showPic']
                ? self::spanCount($rows, $i, $n, fn ($o) => $o['groupIdx'] === $r['groupIdx'] && $o['pageNo'] === $r['pageNo'])
                : 0;

            // Kelas border: segmen 1 baris = border penuh; selain itu top/mid/bot.
            // 'mrg-bot' MEMPERTAHANKAN border-bottom → tepi bawah halaman tertutup.
            $r['mrg'] = match (true) {
                ! $sameAsPrev && ! $sameAsNext => '',
                ! $sameAsPrev => 'mrg-top',
                ! $sameAsNext => 'mrg-bot',
                default => 'mrg-mid',
            };
        }
        unset($r);

        return $rows;
    }

    /** Hitung baris berturut mulai $i (inklusif) yg masih memenuhi $match. */
    private static function spanCount(array $rows, int $i, int $n, callable $match): int
    {
        $span = 1;
        for ($j = $i + 1; $j < $n; $j++) {
            if (! $match($rows[$j])) {
                break;
            }
            $span++;
        }

        return $span;
    }
}
