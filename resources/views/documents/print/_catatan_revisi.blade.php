{{-- Lembar CATATAN REVISI (docs/Lembar Revisi.docx) — halaman DEPAN dokumen,
     hanya tampil bila ada baris catatan_revisi (dokumen hasil revisi Tipe B).
     Kolom: NO | NO. REV | TANGGAL REV | HAL. | CATATAN REVISI. --}}
@php
    $catatanRows = collect(is_array($contentMap['catatan_revisi'] ?? null) ? $contentMap['catatan_revisi'] : [])
        ->filter(fn ($r) => is_array($r))->values();
    $bulanId = [1 => 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
        'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
    $tglId = function ($v) use ($bulanId) {
        try {
            $d = \Carbon\Carbon::parse((string) $v);

            return $d->day.' '.$bulanId[$d->month].' '.$d->year;
        } catch (\Throwable) {
            return (string) $v;   // bukan tanggal valid → tampilkan apa adanya
        }
    };
@endphp
@if ($catatanRows->isNotEmpty())
    <div class="catatan-revisi" style="page-break-after: always">
        <div style="text-align:center; font-weight:bold; font-size:10pt; margin:8pt 0 6pt; text-decoration:underline">CATATAN REVISI</div>
        {{-- Lebar kolom PERSIS grid docx (562|993|1701|850|6662 twips ≈ 5/9/16/8/62),
             ditaruh di TH karena DomPDF mengabaikan colgroup. --}}
        <table style="width:100%; border-collapse:collapse; table-layout:fixed">
            <thead>
                <tr>
                    @foreach (['NO' => 5, 'NO. REV' => 9, 'TANGGAL REV' => 16, 'HAL.' => 8, 'CATATAN REVISI' => 62] as $h => $wPct)
                        <th style="width:{{ $wPct }}%; border:0.75pt solid #000; background:#EDEDED; padding:3pt 5pt; font-size:8pt; text-align:center">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($catatanRows as $i => $r)
                    <tr style="page-break-inside:avoid">
                        <td style="border:0.75pt solid #000; padding:3pt 5pt; font-size:8pt; text-align:center">{{ $i + 1 }}</td>
                        <td style="border:0.75pt solid #000; padding:3pt 5pt; font-size:8pt; text-align:center">{{ str_pad((string) (int) ($r['no_rev'] ?? 0), 2, '0', STR_PAD_LEFT) }}</td>
                        <td style="border:0.75pt solid #000; padding:3pt 5pt; font-size:8pt; text-align:center">{{ $tglId($r['tanggal'] ?? '') }}</td>
                        <td style="border:0.75pt solid #000; padding:3pt 5pt; font-size:8pt; text-align:center">{{ $r['halaman'] ?? '' }}</td>
                        <td style="border:0.75pt solid #000; padding:3pt 5pt; font-size:8pt">{{ $r['catatan'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
