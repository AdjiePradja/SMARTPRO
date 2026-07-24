@php
    $screen = $screen ?? false;
    $orientation = $orientation ?? 'portrait';
    $paperWidth = $orientation === 'landscape' ? '297mm' : '210mm';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        /* Tipografi selaras referensi (template_sop): lebih lega & terbaca —
           line-height 1.5, body sedikit diperbesar (8→9pt). Kop tetap seperti
           kalibrasi cm/16pt (konfigurasi inti, tak diubah). */
        body { font-family: Arial, "DejaVu Sans", sans-serif; font-size: 9pt; line-height: 1.5; color: #000; margin: 0; }

        /* Kop BERULANG tiap halaman via position:fixed (BUKAN thead page-frame):
           dgn page-frame, tabel isi jadi bersarang ganda -> DomPDF tak bisa
           memecahnya antar halaman (aktivitas panjang meluber ke dasar / terpotong).
           Kop fixed di area margin atas; SEMUA tabel isi jadi top-level -> mengalir
           & mengisi halaman, hormati margin bawah 2cm (57pt). */
        @page { margin: 162pt 28pt 57pt 28pt; }
        .kop-fixed { position: fixed; top: -146pt; left: 0; right: 0; }
        @if ($screen)
            body { width: {{ $paperWidth }}; margin: 0 auto; padding: 16pt 24pt; background: #fff; }
            .kop-fixed { position: static; margin-bottom: 8pt; }
        @endif

        .page-footer { margin-top: 8pt; }
        .doc-footer { font-size: 7pt; color: #2a5bd7; font-style: italic; }

        /* Kop: lebar kolom 4cm/8cm/6cm; JENIS & JUDUL 2x (jenis 11.5->23, judul
           9->18); ikon mengisi kolom 4cm. */
        table.kop { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.kop td { border: 0.75pt solid #000; padding: 2pt 5pt; font-size: 8pt; vertical-align: middle; line-height: 1.2; }
        .kop-logo { text-align: center; vertical-align: middle; padding: 2pt; }
        .kop-logo img { width: 108pt; height: 108pt; display: inline-block; vertical-align: middle; }   /* ikon mengisi kolom 4cm (2x-an) */
        .kop-logo-ph { font-weight: bold; font-size: 22pt; color: #c00; }
        /* PENTING — pakai td.xxx, BUKAN .xxx: `table.kop td` (spesifisitas 0,1,2)
           MENGALAHKAN `.kop-title` (0,1,0), jadi font-size di kelas polos TIDAK
           PERNAH terpakai (tetap 8pt). Ini sebab kop tak kunjung membesar. */
        table.kop td.kop-title { text-align: center; font-weight: bold; font-size: 14pt; line-height: 1.12; }      /* JENIS dokumen */
        table.kop td.kop-subject { text-align: center; font-weight: bold; font-size: 12pt; line-height: 1.15; }    /* JUDUL dokumen */
        /* nowrap: sufiks "(sementara)" tak boleh membungkus baris -> tinggi kop
           draft = approved (posisi stamp Halaman stabil). */
        .kop-meta { font-size: 8pt; height: 18pt; white-space: nowrap; }

        /* Bilah judul bab & kotak isi — padding lega spt referensi (5px/10px). */
        .section-bar { background: #EDEDED; border: 0.75pt solid #000; font-weight: bold; padding: 4pt 7pt; font-size: 9pt; margin-top: 8pt; }
        .section-box { border: 0.75pt solid #000; border-top: none; padding: 7pt 8pt; }

        /* Body teks justified + HANGING INDENT lewat kolom nomor terpisah: baris
           lanjutan otomatis sejajar awal teks setelah nomor (bukan nomornya).
           Kolom nomor SEMPIT (jarak dari bullet kecil, #5). */
        table.list { width: 100%; border-collapse: collapse; }
        table.list td { padding: 2.5pt 4pt; vertical-align: top; font-size: 9pt; text-align: justify; line-height: 1.5; }
        table.list td.num { width: 26pt; white-space: nowrap; text-align: left; padding-right: 4pt; }   /* kolom nomor lega (referensi 35px) */
        table.list tr { page-break-inside: avoid; }

        /* AKTIVITAS: 2 kolom (AKTIVITAS | PIC) spt referensi. Nomor "5.x" DIGABUNG
           di sel AKTIVITAS (bukan kolom sendiri) sbg <span> inline-block lebar
           tetap → hanging indent PERSIS: judul & deskripsi sejajar setelah nomor.
           Deskripsi dipecah per-paragraf → baris kecil yg mengalir mengisi halaman. */
        table.akt { width: 100%; border-collapse: collapse; table-layout: fixed; }
        /* word-break: kata panjang tanpa spasi tak boleh meluber menembus kolom. */
        table.akt th, table.akt td { border: 0.75pt solid #000; padding: 6pt 6pt; font-size: 9pt; vertical-align: top; line-height: 1.5; overflow-wrap: anywhere; }
        table.akt th { background: #EDEDED; text-align: center; vertical-align: middle; }
        /* HANGING INDENT tanpa text-indent: DomPDF menerapkan text-indent negatif
           DUA KALI (terukur: nomor meleset 40pt utk -20pt) sehingga nomor keluar
           kotak. Pakai margin-left negatif pada span nomor: sel diberi padding
           25pt, nomor ditarik 20pt → nomor di 5pt (DALAM kotak), teks & baris
           lanjutan sama-sama mulai di 25pt. */
        /* Deskripsi = banyak baris tabel kecil (mengalir antar-halaman → aktivitas
           panjang tak meluber margin bawah). Padding VERTIKAL kecil (2pt) di antara
           baris satu grup → sub-judul & deskripsi MENEMPEL (tak ada celah baris
           kosong, #3); baris pertama/terakhir grup diberi 6pt agar tetap berjarak
           dari garis atas/bawah. */
        /* padding-left 46pt & .jn width 34pt: menyamakan indentasi AKTIVITAS (sub-bab
           5) dgn daftar sub-bab 1–6 (nomor x≈40.8pt, teks x≈74.8pt — terukur dari
           stream). Sebelumnya akt lebih rapat (teks 53.8pt) → tampak beda (#8). */
        table.akt td.aktcell, table.akt td.aktcont { text-align: justify; padding-left: 46pt; padding-top: 2pt; padding-bottom: 2pt; }
        table.akt td.mrg-top { padding-top: 6pt; }      /* baris pertama grup: napas atas */
        table.akt td.mrg-bot { padding-bottom: 6pt; }   /* baris terakhir grup: napas bawah */
        /* vertical-align:top — baseline inline-block di DomPDF membuat nomor
           mengambang 2.7pt DI ATAS judulnya (terukur). Dgn top, kotak nomor rata
           atas dgn baris → baseline nomor & judul PERSIS sejajar. */
        table.akt td.aktcell .jn { display: inline-block; width: 34pt; margin-left: -34pt; font-weight: bold; vertical-align: -3.3pt; line-height: 1.5; }
        /* Simulasi sel gabung kolom AKTIVITAS: hilangkan garis antar-baris satu grup. */
        table.akt td.mrg-top { border-bottom: none; }
        table.akt td.mrg-mid { border-top: none; border-bottom: none; }
        table.akt td.mrg-bot { border-top: none; }
        /* PIC = SATU sel rowspan menutupi seluruh grup → vertical-align:middle
           membuatnya RATA TENGAH atas-bawah apa pun panjang deskripsi (#3). */
        table.akt td.pic { text-align: center; vertical-align: middle; }
        table.akt tbody tr { page-break-inside: avoid; }

        /* Pengesahan SELALU di halaman tersendiri (permintaan #7): jangan digabung
           dgn ekor isi. page-break-before memaksa mulai halaman baru. */
        .pengesahan-page { page-break-before: always; }
        table.pengesahan { width: 100%; border-collapse: collapse; }
        table.pengesahan th, table.pengesahan td { border: 0.75pt solid #000; padding: 6pt 6pt; font-size: 9pt; }
        table.pengesahan th { background: #EDEDED; text-align: center; }
        table.pengesahan tr { page-break-inside: avoid; }
        .center { text-align: center; }
        .peng-role { font-size: 7pt; color: #444; }
        .stamp { display: inline-block; border: 1.5pt solid #1a7f37; color: #1a7f37; font-weight: bold; padding: 2pt 9pt; font-size: 10pt; letter-spacing: 1px; }

        .lampiran-judul { font-weight: bold; }
        .lampiran-img { max-width: 240pt; max-height: 190pt; margin-top: 3pt; page-break-inside: avoid; }
    </style>
</head>
<body>
    {{-- Kop BERULANG tiap halaman (fixed); isi mengalir top-level. --}}
    <div class="kop-fixed">@include('documents.print._kop')</div>

    {{-- Lembar CATATAN REVISI di halaman DEPAN (hanya dokumen hasil revisi) --}}
    @if (! empty(array_filter((array) ($contentMap['catatan_revisi'] ?? []))))
        @include('documents.print._catatan_revisi')
    @endif

    @foreach ($schema->allSections() as $section)
        @php
            $type = $section['type'] ?? 'text';
            $val = $contentMap[$section['key']] ?? null;
        @endphp

        @if (in_array($type, ['rich_list', 'reference_picker']))
            <div class="section-bar">{{ $section['label'] }}</div>
            <div class="section-box">
                <table class="list">
                    @foreach ((is_array($val) ? $val : []) as $i => $item)
                        <tr><td class="num">{{ ($section['auto_number'] ?? '') }}{{ $i + 1 }}</td><td>{{ $item }}</td></tr>
                    @endforeach
                </table>
            </div>

        @elseif ($type === 'repeatable_group')
            @php $fields = collect($section['group_fields'] ?? $section['fields'] ?? []); $hasPic = $fields->contains('key', 'pic'); @endphp

            @if ($hasPic)
                <div class="section-bar">{{ $section['label'] }}</div>
                <table class="akt">
                    {{-- Lebar di TH (DomPDF mengabaikan colgroup): AKTIVITAS 75% | PIC 25%. --}}
                    <thead><tr><th style="width:75%">AKTIVITAS</th><th style="width:25%">PIC</th></tr></thead>
                    <tbody>
                        {{-- Baris ter-PAGINASI dari mesin 2-fase (DocumentController::
                             renderStandardPaginated → ActivityPrintLayout): rowspan PIC &
                             kelas border DI-SCOPE PER HALAMAN sehingga tepi tabel selalu
                             TERTUTUP di batas halaman & PIC tak hilang saat aktivitas
                             panjang terpotong. Bila TAK dikirim (preview layar/fallback) →
                             susun sekali jalan (satu segmen per grup). --}}
                        @php
                            $rowsAkt = ($aktRows[$section['key']] ?? null)
                                ?? \App\Services\ActivityPrintLayout::plan(
                                    \App\Services\ActivityPrintLayout::flatten(is_array($val) ? $val : [], $section['auto_number'] ?? '')
                                );
                        @endphp
                        @foreach ($rowsAkt as $ri => $r)
                            {{-- Anchor 1pt putih [[Ai]] (tak terlihat) di AKHIR sel → penanda
                                 pengukuran Fase 1. WAJIB di akhir: bila di depan, lebarnya
                                 (±2.9pt) menggeser BARIS PERTAMA ke kanan sehingga baris
                                 ke-2 dst. tak sejajar. --}}
                            @php $anchor = '<span style="font-size:1pt;color:#fff">[[A'.$ri.']]</span>'; @endphp
                            @if ($probeFlat ?? false)
                                {{-- PROBE DATAR (diukur lalu dibuang): tanpa rowspan & tanpa
                                     kelas border → DomPDF memaginasi bersih. --}}
                                <tr>
                                    <td class="{{ $r['isHead'] ? 'aktcell' : 'aktcont' }}" style="width:75%">@if($r['isHead'])<span class="jn">{{ $r['number'] }}</span><strong>{{ $r['text'] }}</strong>@else{{ $r['text'] }}@endif{!! $anchor !!}</td>
                                    <td class="pic" style="width:25%">@if($r['isHead']){{ $r['pic'] }}@endif</td>
                                </tr>
                            @else
                                <tr @if(! $screen && ($r['pageBreakBefore'] ?? false)) style="page-break-before: always;" @endif>
                                    <td class="{{ $r['isHead'] ? 'aktcell' : 'aktcont' }} {{ $r['mrg'] }}" style="width:75%">@if($r['isHead'])<span class="jn">{{ $r['number'] }}</span><strong>{{ $r['text'] }}</strong>@else{{ $r['text'] }}@endif{!! $anchor !!}</td>
                                    @if ($r['showPic'])<td class="pic" style="width:25%" rowspan="{{ $r['picRowspan'] }}">{{ $r['pic'] }}</td>@endif
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @else
                {{-- Lampiran: header bar + tiap item satu blok (foto tak terpotong) --}}
                <div class="section-bar">{{ $section['label'] }}</div>
                @foreach ((is_array($val) ? $val : []) as $i => $row)
                    <div class="section-box" style="border-top:0.75pt solid #000; page-break-inside:avoid">
                        <table class="list"><tr>
                            <td class="num">{{ $i + 1 }}.</td>
                            <td>
                                <span class="lampiran-judul">{{ $row['judul'] ?? '' }}</span>
                                @php $ket = $row['keterangan'] ?? ($row['isi'] ?? ''); @endphp
                                @if (is_string($ket) && $ket !== '' && ! str_starts_with($ket, 'lampiran/'))<br>{{ $ket }}@endif
                                @php $gambar = $row['gambar'] ?? (is_string($row['isi'] ?? '') && str_starts_with($row['isi'] ?? '', 'lampiran/') ? $row['isi'] : ''); @endphp
                                @if (is_string($gambar) && str_starts_with($gambar, 'lampiran/') && $embed($gambar))<br><img class="lampiran-img" src="{{ $embed($gambar) }}" alt="lampiran">@endif
                            </td>
                        </tr></table>
                    </div>
                @endforeach
            @endif
        @endif
    @endforeach

    <div class="pengesahan-page">@include('documents.print._pengesahan')</div>

    {{-- Footer: mengalir setelah isi → sekali saja, di halaman terakhir. --}}
    <div class="page-footer">@include('documents.print._footer')</div>
</body>
</html>
