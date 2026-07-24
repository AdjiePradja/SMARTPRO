<?php

namespace App\Services;

/**
 * Tata letak tabel analisa JSA untuk cetak PDF — mesin paginasi 2-fase dgn
 * ROWSPAN ASLI (spt docs/JSA new.docx).
 *
 * MODEL: satu BARIS per pengendalian. Sel "Uraian Langkah" digabung (rowspan)
 * melintasi seluruh bahaya+pengendalian miliknya; sel "Bahaya" digabung
 * melintasi seluruh pengendalian miliknya. Efeknya:
 *   - Langkah & Bahaya RATA TENGAH atas-bawah pd tinggi gabungannya (celah kosong
 *     hilang) — vertical-align:middle.
 *   - Bila Bahaya lebih tinggi dari total pengendalian, DomPDF membagi tinggi
 *     lebihnya ke baris-baris pengendalian → 1.1.1/1.1.2/1.1.3 PROPORSIONAL.
 *   - Bahaya MENGUASAI semua pengendalian di bawahnya (cover benar).
 *
 * MASALAH DomPDF: (a) tak bisa memecah SATU baris antar halaman; (b) sel rowspan
 * yang MELINTASI batas halaman rusak. SOLUSI 2-fase (DocumentController):
 *   1) Render PROBE "datar" (tiap sel di barisnya sendiri, TANPA rowspan) →
 *      DomPDF memaginasi bersih → ukur (anchor 1pt [[Ri]]) baris awal tiap halaman.
 *      Tinggi datar SELALU ≥ tinggi rowspan (bukti: sel bahaya panjang di layout
 *      datar menempati baris pertama + baris pengendalian sisanya; di rowspan ia
 *      hanya setinggi max) → segmen final PASTI muat, tak pernah terpotong.
 *   2) plan() menetapkan pageNo + rowspan DI-SCOPE per halaman: Langkah/Bahaya
 *      yang berlanjut DIULANG di awal halaman berikut dgn rowspan baru → tak ada
 *      sel rowspan yang melintasi halaman. page-break-before dipaksa di awal
 *      segmen agar DomPDF menghormati titik-potong hasil ukur.
 *
 * Kelas MURNI (tanpa render) → mudah diuji & direview.
 */
class JsaPrintLayout
{
    /**
     * Ratakan analisa nested → baris DATAR, satu per pengendalian. Nomor Langkah/
     * Bahaya/Kendali disimpan di SETIAP baris (dipakai saat sel diulang di awal
     * halaman lanjutan). Posisi array == indeks (dipakai anchor pengukuran F1).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function flatten(array $analisa): array
    {
        $rows = [];
        foreach (array_values($analisa) as $li => $step) {
            $n = $li + 1;
            $langkahText = (string) ($step['langkah'] ?? '');
            $bahayaList = is_array($step['bahaya'] ?? null) && $step['bahaya'] !== []
                ? array_values($step['bahaya'])
                : [['risiko' => '', 'pengendalian' => []]];

            foreach ($bahayaList as $bi => $b) {
                $risiko = trim((string) ($b['risiko'] ?? ''));
                $pengList = array_values(array_filter(
                    array_map(fn ($p) => trim((string) $p), (array) ($b['pengendalian'] ?? [])),
                    fn ($p) => $p !== ''
                ));
                if ($pengList === []) {
                    $pengList = [''];   // pastikan ≥ 1 baris agar bahaya tetap tampil
                }

                foreach ($pengList as $pi => $p) {
                    $rows[] = [
                        'stepIdx' => $li,
                        'bahayaIdx' => $bi,
                        'pengIdx' => $pi,
                        'stepNo' => $n.'.',
                        'langkahText' => $langkahText,
                        'bahayaNo' => $n.'.'.($bi + 1),
                        'bahayaText' => $risiko,
                        'kendaliNo' => $p === '' ? '' : $n.'.'.($bi + 1).'.'.($pi + 1),
                        'kendaliText' => $p,
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * Susun render-rows dari baris + himpunan indeks awal-halaman ($pageStarts,
     * hasil ukur F1). Menetapkan pageNo/pageBreakBefore, lalu — DI-SCOPE PER
     * HALAMAN — showLangkah/stepRowspan & showBahaya/bahayaRowspan sehingga tak
     * ada sel rowspan yang melintasi batas halaman (Langkah/Bahaya yg berlanjut
     * diulang dgn rowspan baru di halaman berikut).
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

            // Awal segmen = beda step/bahaya ATAU pindah halaman (scope per halaman).
            $sameStepPage = $prev && $prev['stepIdx'] === $r['stepIdx'] && $prev['pageNo'] === $r['pageNo'];
            $sameBahayaPage = $sameStepPage && $prev['bahayaIdx'] === $r['bahayaIdx'];

            $r['showLangkah'] = ! $sameStepPage;
            $r['showBahaya'] = ! $sameBahayaPage;

            // Rowspan = jumlah baris berikutnya yg masih step/bahaya & halaman sama.
            $r['stepRowspan'] = $r['showLangkah']
                ? self::spanCount($rows, $i, $n, fn ($o) => $o['stepIdx'] === $r['stepIdx'] && $o['pageNo'] === $r['pageNo'])
                : 0;
            $r['bahayaRowspan'] = $r['showBahaya']
                ? self::spanCount($rows, $i, $n, fn ($o) => $o['stepIdx'] === $r['stepIdx'] && $o['bahayaIdx'] === $r['bahayaIdx'] && $o['pageNo'] === $r['pageNo'])
                : 0;
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
