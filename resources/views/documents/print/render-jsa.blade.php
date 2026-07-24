@php
    $screen = $screen ?? false;
    // probeFlat: tata letak PROBE datar (tanpa rowspan) khusus pengukuran Fase 1
    // (DocumentController::renderJsaPaginated). Render FINAL & preview = false → rowspan.
    $probeFlat = $probeFlat ?? false;
    $orientation = $orientation ?? 'landscape';
    // Lebar kertas utk PREVIEW di layar (browser mengabaikan @page). Diambil dari
    // orientasi schema yang sama dengan yang dipakai PDF → tak mungkin beda.
    $paperWidth = $orientation === 'landscape' ? '297mm' : '210mm';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, "DejaVu Sans", sans-serif; font-size: 8pt; line-height: 1.3; color: #000; margin: 0; }

        /* Kop atas HANYA DI HALAMAN 1 (permintaan #7): kop mengalir biasa (static),
           bukan position:fixed, sehingga TIDAK terulang. Halaman 2 dst. cukup
           menampilkan HEADER TABEL analisa yang berulang lewat thead. Karena kop
           tak lagi dipesan di area margin, margin atas @page mengecil drastis
           (139pt → 20pt) → ruang isi jauh lebih banyak. Blok info+TTD tetap
           mengalir biasa → hanya halaman 1. */
        {{-- Kiri/kanan 8pt ≈ referensi (pgMar 141 twips — nyaris full-bleed);
             bawah 57pt ≈ 2cm agar teks tak menyentuh dasar halaman. --}}
        @page { margin: 20pt 8pt 57pt 8pt; }
        {{-- Kop mengalir (static) → muncul di lembar revisi & di awal body JSA
             (dua include terpisah, lihat body). Bukan berulang tiap halaman. --}}
        .kop-block { margin-bottom: 0; }
        @if ($screen)
            body { width: {{ $paperWidth }}; margin: 0 auto; padding: 16pt 20pt; background: #fff; }
        @endif
        /* Footer mengalir di akhir isi → tampil SEKALI di halaman terakhir dan
           tak pernah menabrak isi (position:fixed dulu terulang tiap halaman
           sekaligus menimpa tabel). */
        .page-footer { margin-top: 8pt; }
        .doc-footer { font-size: 7pt; color: #2a5bd7; font-style: italic; }

        /* Kop. nowrap: nilai panjang (mis. "(sementara)" di draft) tidak boleh
           membungkus baris — tinggi kop harus SAMA antara draft & approved agar
           stamp "Halaman X dari Y" selalu pas di selnya. */
        /* Kop DIRAMPINGKAN: logo 92→52pt, padding & tinggi baris dikecilkan lagi
           (~20%, v12), judul 16→13pt → tinggi kop turun banyak, ruang tabel bertambah. */
        table.kop { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.kop td { border: 0.75pt solid #000; padding: 0.4pt 5pt; font-size: 7.5pt; line-height: 1.1; vertical-align: middle; white-space: nowrap; }
        .kop-logo { text-align: center; vertical-align: middle; padding: 1pt; }
        .kop-logo img { width: 52pt; height: 52pt; display: inline-block; vertical-align: middle; }
        .kop-logo-ph { font-weight: bold; font-size: 14pt; color: #c00; }
        /* PENTING — pakai td.kop-title: `table.kop td` (0,1,2) mengalahkan
           `.kop-title` (0,1,0) → font-size tak pernah terpakai (tetap 8pt). */
        table.kop td.kop-title { text-align: center; font-weight: bold; font-style: italic; font-size: 13pt; letter-spacing: .5px; line-height: 1.1; white-space: normal; }  /* FORMULIR JSA */
        .kop .mk { font-weight: bold; }

        /* Info umum + tanda tangan digabung dalam satu tabel (spt referensi).
           margin-top:0 → NEMPEL dgn kop atas (permintaan). */
        table.head-block { width: 100%; border-collapse: collapse; margin-top: 0; table-layout: fixed; }
        table.head-block td { border: 0.75pt solid #000; padding: 1.5pt 5pt; font-size: 8pt; vertical-align: top; line-height: 1.2; }
        /* Label 1 baris (nowrap) spt referensi (docs/JSA new.docx #9a): "Peralatan
           yang digunakan" tak turun ke baris kedua → head-block lebih pendek. */
        table.head-block td.lbl { font-weight: bold; white-space: nowrap; }
        table.head-block td.ttd-head { text-align: center; font-weight: bold; }
        table.head-block td.ttd-stamp { text-align: center; vertical-align: middle; height: 40pt; }
        .stamp { display: inline-block; border: 1.5pt solid #1a7f37; color: #1a7f37; font-weight: bold; padding: 2pt 10pt; font-size: 11pt; letter-spacing: 1px; transform: rotate(-8deg); }

        /* Tabel analisa — ROWSPAN asli (sel gabung cukup tinggi → tak ada teks
           meluber / tumpang tindih). Header berulang tiap halaman.
           HANGING INDENT: nomor = <span class="jn"> inline-block lebar TETAP,
           padding-left = lebar itu + text-indent negatif → teks setelah nomor &
           baris lanjutan sejajar PERSIS (bukan sejajar nomor). */
        table.jsa { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 0; }
        table.jsa > thead { display: table-header-group; }   /* header BERULANG tiap halaman */
        {{-- vertical-align: middle → sel Langkah/Bahaya (rowspan) & pengendalian RATA
             TENGAH atas-bawah pd tinggi selnya → tak ada celah kosong (#5/#6/#7). --}}
        /* Tinggi baris DIRAMPINGKAN ~20% (v12): padding 3→1.5pt & line-height 1.25
           → baris ±13pt (≈0.46cm) dari ±16.4pt (≈0.58cm). */
        table.jsa th, table.jsa td { border: 0.75pt solid #000; padding: 1.5pt 5pt; font-size: 8pt; line-height: 1.25; vertical-align: middle; }
        /* HANGING INDENT tanpa text-indent: DomPDF menerapkan text-indent negatif
           DUA KALI sehingga nomor keluar kotak. Pakai margin-left negatif pada
           span nomor → nomor tetap DALAM kotak (5pt dari garis), teks & baris
           lanjutan sama-sama mulai di padding-left. */
        /* Kata SANGAT panjang tanpa spasi (mis. teks tempel/uji) tak punya titik
           putus → DomPDF membiarkannya meluber MENEMBUS kolom sebelah. Paksa
           pemenggalan di tengah kata agar teks selalu tinggal di dalam selnya. */
        /* DomPDF MENGABAIKAN `word-break` (FrameReflower/Text hanya membaca
           overflow_wrap). `anywhere` = satu-satunya nilai yang memenggal di tengah
           kata → teks panjang tanpa spasi tak lagi menembus kolom sebelah. */
        table.jsa tbody td { text-align: justify; overflow-wrap: anywhere; }
        /* vertical-align: baseline inline-block DomPDF membuat nomor mengambang di
           ATAS teksnya; -2.1pt (terkalibrasi dari stream) menyejajarkannya. */
        table.jsa .jn { display: inline-block; font-weight: normal; vertical-align: -2.1pt; line-height: 1.3; }
        /* Lebar kotak nomor (.jn) = lebar nomor terpanjang realistis + JARAK sebelum
           teks (celah = width − lebar nomor). Baris lanjutan sejajar di padding-left
           (= width + 5pt margin nomor dari border). Nomor kendali "N.M.K" dulu terlalu
           pas (nempel ke teks) → dilebarkan agar selalu ada celah. */
        table.jsa td.jc-langkah { padding-left: 19pt; }
        table.jsa td.jc-langkah .jn { width: 14pt; margin-left: -14pt; font-weight: bold; }
        table.jsa td.jc-bahaya  { padding-left: 23pt; }
        table.jsa td.jc-bahaya .jn { width: 18pt; margin-left: -18pt; }
        table.jsa td.jc-kendali { padding-left: 31pt; }
        table.jsa td.jc-kendali .jn { width: 26pt; margin-left: -26pt; }
        /* Kolom "Beri tanda": KOTAK centang di tiap baris pengendalian. Hanya tanda
           TERPILIH peninjau (✓/✗) yang tampil; kosong = kotak polos (#2). */
        table.jsa td.jc-check { text-align: center; vertical-align: middle; }
        .chkbox { display: inline-block; width: 11pt; height: 11pt; border: 0.75pt solid #000; line-height: 10pt; font-size: 9pt; text-align: center; font-weight: bold; }
        /* Header rata tengah (vertikal & horizontal), berulang tiap halaman (#8). */
        table.jsa th { background: #D9E1F2; text-align: center; font-weight: bold; vertical-align: middle; text-indent: 0; padding-left: 5pt; line-height: 1.08; }
        /* DomPDF tak bisa memecah satu baris antar-halaman → baris SANGAT tinggi (mis.
           satu pengendalian belasan baris) MELUBER menembus margin bawah. page-break-
           inside: avoid memaksa baris seperti itu PINDAH UTUH ke halaman berikut.
           Fase-1 (probe datar) memakai CSS yg sama → titik-potong konsisten dgn final.
           Data normal (pengendalian pendek) tak ada baris di-bump → halaman rapat. */
        table.jsa tbody tr { page-break-inside: avoid; }
    </style>
</head>
<body>
@php
    $jabatanLabel = function ($u) {
        if (! $u) return '-';
        return match ($u->jabatan) {
            'pimpinan' => 'PJO', 'section_head' => 'Section Head',
            'group_leader' => 'Group Leader', 'staff' => 'Staff', default => $u->jabatan ?? '-',
        };
    };
    // Sudah pernah Berlaku (disetujui PJO) → SELURUH TTD bercap APPROVED. Memakai
    // published_at agar dokumen "Sedang Direvisi"/obsolete (yang dulu disahkan)
    // tetap menampilkan capnya.
    $published = $document->published_at !== null;
    $analisa = is_array($contentMap['analisa'] ?? null) ? $contentMap['analisa'] : [];

    // Baris analisa ter-PAGINASI dari mesin 2-fase (DocumentController::renderJsaPaginated
    // → JsaPrintLayout). $analisaRows berisi flag showLangkah/showBahaya (header
    // diulang di tiap halaman lanjutan spt docs/JSA new.docx) + kelas border mrg-*
    // yang menutup tepi tiap halaman. Bila TAK dikirim (preview layar / fallback) →
    // susun sekali jalan (alir biasa, tanpa pengulangan header).
    $rows = $analisaRows ?? \App\Services\JsaPrintLayout::plan(\App\Services\JsaPrintLayout::flatten($analisa));
    $listText = function ($v) {
        if (is_array($v)) return implode(', ', array_filter(array_map('trim', $v), fn ($x) => $x !== ''));
        return is_string($v) ? $v : '';
    };
    $apd = $listText($contentMap['apd'] ?? null);
    $tools = $listText($contentMap['tools'] ?? null);

    // Checklist peninjau (#2): peta {refP: 'check'|'cross'} → kotak di kolom
    // "Beri tanda". HANYA tanda TERPILIH yang tercetak (1 kotak/baris pengendalian).
    // Kunci sama dgn halaman peninjauan: L{stepIdx}-B{bahayaIdx}-P{pengIdx}.
    $checklist = is_array($contentMap['jsa_checklist'] ?? null) ? $contentMap['jsa_checklist'] : [];
    $markBox = function ($r) use ($checklist) {
        if (($r['kendaliNo'] ?? '') === '') {
            return '';   // baris bahaya tanpa pengendalian → tanpa kotak
        }
        $mk = $checklist['L'.$r['stepIdx'].'-B'.$r['bahayaIdx'].'-P'.$r['pengIdx']] ?? null;
        $inner = $mk === 'check' ? '<span style="color:#1a7f37">&#10003;</span>'
            : ($mk === 'cross' ? '<span style="color:#c00">&#10007;</span>' : '');

        return '<span class="chkbox">'.$inner.'</span>';
    };
    // Cap PNG (total ±45°) di tengah sel TTD + marker teks 1pt (utk PrintLayoutTest).
    $stampCell = function () use ($published, $stamp) {
        if (! $published) return '';
        return $stamp
            ? '<img src="'.$stamp.'" alt="APPROVED" style="height:34pt; vertical-align:middle"><span style="font-size:1pt;color:#fff">APPROVED</span>'
            : '<span class="stamp">APPROVED</span>';
    };
@endphp
@php $hasRevisi = ! empty(array_filter((array) ($contentMap['catatan_revisi'] ?? []))); @endphp
{{-- Anchor [[KOP]] (1pt putih) → penanda halaman berkop utk stamp nomor halaman. --}}
@php $kopAnchor = '<span style="font-size:1pt;color:#fff">[[KOP]]</span>'; @endphp

{{-- KOP #1 — hanya bila ada lembar revisi: kop di halaman CATATAN REVISI. --}}
@if ($hasRevisi)
    <div class="kop-block">{!! $kopAnchor !!}@include('documents.print._kop_jsa')</div>
    @include('documents.print._catatan_revisi')
@endif

{{-- KOP #2 — SELALU: kop di halaman PERTAMA body JSA. Tanpa revisi → halaman 1;
     dengan revisi → halaman 2 (catatan_revisi punya page-break-after). --}}
<div class="kop-block">{!! $kopAnchor !!}@include('documents.print._kop_jsa')</div>

        {{-- Informasi umum (kiri) + Tanda tangan (kanan) — satu tabel, spt referensi --}}
            <table class="head-block">
                {{-- Lebar di sel baris pertama (DomPDF mengabaikan colgroup):
                     label 15% | nilai 25% | TTD 3×20%. Label dilebarkan agar
                     "Peralatan yang digunakan" muat 1 baris (nowrap). --}}
                <tr>
                    <td class="lbl" style="width:15%">No. Pekerjaan/JSA</td>
                    <td style="width:25%">: {{ $document->displayNumber() }}@unless($document->hasFinalNumber()) (sementara)@endunless</td>
                    <td class="ttd-head" style="width:20%">Dibuat Oleh,</td>
                    <td class="ttd-head" style="width:20%">Direview Oleh,</td>
                    <td class="ttd-head" style="width:20%">Disetujui Oleh,</td>
                </tr>
                <tr>
                    <td class="lbl">Tanggal Pembuatan</td>
                    <td>: {{ $document->created_at?->format('Y-m-d') ?? '-' }}</td>
                    <td class="ttd-stamp" rowspan="4">{!! $stampCell() !!}</td>
                    <td class="ttd-stamp" rowspan="4">{!! $stampCell() !!}</td>
                    <td class="ttd-stamp" rowspan="4">{!! $stampCell() !!}</td>
                </tr>
                <tr><td class="lbl">Nama Pekerjaan</td><td>: {{ $document->title }}</td></tr>
                <tr><td class="lbl">Departemen</td><td>: {{ $document->department->code ?? '-' }}</td></tr>
                <tr><td class="lbl">Lokasi kerja</td><td>: {{ $contentMap['lokasi_kerja'] ?? '-' }}</td></tr>
                <tr>
                    <td class="lbl">APD yang digunakan</td>
                    <td>: {{ $apd !== '' ? $apd : '-' }}</td>
                    <td>Nama : {{ $document->creator->name ?? '-' }}</td>
                    <td>Nama : {{ $document->reviewer->name ?? '-' }}</td>
                    <td>Nama : {{ $document->approver->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Peralatan yang digunakan</td>
                    <td>: {{ $tools !== '' ? $tools : '-' }}</td>
                    <td>Jabatan : {{ $jabatanLabel($document->creator) }}</td>
                    <td>Jabatan : {{ $jabatanLabel($document->reviewer) }}</td>
                    <td>Jabatan : {{ $jabatanLabel($document->approver) }}</td>
                </tr>
            </table>

        {{-- Tabel analisa — ROWSPAN asli (langkah span bahaya, bahaya span kendali);
             penomoran hirarkis 1 / 1.1 / 1.1.1; hanging indent via <span class="jn">. --}}
            <table class="jsa">
                {{-- Lebar di TH (DomPDF mengabaikan colgroup). Referensi memakai
                     50/10, tapi kolom centang 10% membuat header (teks panjang)
                     jadi sangat TINGGI → DomPDF berhenti mengulang thead antar
                     halaman. Titik tengah 46/14: kolom Tindakan Pengendalian tetap
                     LEBAR (prioritas isi kendali) & header tetap berulang. --}}
                <thead><tr>
                    <th style="width:20%">Uraian Langkah Pekerjaan</th>
                    <th style="width:20%">Bahaya dan Risiko</th>
                    <th style="width:46%">Tindakan Pengendalian</th>
                    <th style="width:14%">Beri tanda (&#10003;) apabila tindakan pengendalian telah sesuai dan diterapkan pada pekerjaan yang dilakukan.</th>
                </tr></thead>
                <tbody>
                    @forelse ($rows as $i => $row)
                        {{-- Anchor 1pt putih [[Ri]] (tak terlihat) di AKHIR sel kendali →
                             penanda pengukuran Fase 1. WAJIB di akhir: bila ditaruh di
                             DEPAN, lebarnya (±2.9pt) menggeser BARIS PERTAMA ke kanan
                             sehingga baris ke-2 dst. tidak sejajar (bug indentasi #7). --}}
                        @php $anchor = '<span style="font-size:1pt; color:#fff">[[R'.$i.']]</span>'; @endphp
                        @if ($probeFlat)
                            {{-- PROBE DATAR (diukur, lalu dibuang): tiap sel di barisnya
                                 sendiri TANPA rowspan → DomPDF memaginasi bersih. Teks
                                 Langkah/Bahaya di baris pertamanya (tinggi ≥ rowspan). --}}
                            <tr>
                                <td class="jc-langkah">@if($row['showLangkah'])<span class="jn">{{ $row['stepNo'] }}</span>{{ $row['langkahText'] }}@endif</td>
                                <td class="jc-bahaya">@if($row['showBahaya'])<span class="jn">{{ $row['bahayaNo'] }}</span>{{ $row['bahayaText'] }}@endif</td>
                                <td class="jc-kendali">@if($row['kendaliNo'] !== '')<span class="jn">{{ $row['kendaliNo'] }}</span>@endif{{ $row['kendaliText'] }}{!! $anchor !!}</td>
                                <td class="jc-check">{!! $markBox($row) !!}</td>
                            </tr>
                        @else
                            {{-- FINAL: ROWSPAN asli, di-scope per halaman + page-break DIPAKSA
                                 di titik potong (hasil ukur ALIRAN ALAMI DomPDF → mengisi
                                 halaman PENUH). Forced break menjamin DomPDF memotong PERSIS
                                 di titik yg di-scope → scope selalu cocok (tak ada sel Langkah/
                                 Bahaya kosong) & border tertutup; Langkah/Bahaya yg berlanjut
                                 diulang di atas halaman lanjutan. --}}
                            <tr @if(! $screen && ($forceBreaks ?? true) && ($row['pageBreakBefore'] ?? false)) style="page-break-before: always;" @endif>
                                @if ($row['showLangkah'])<td class="jc-langkah" rowspan="{{ $row['stepRowspan'] }}"><span class="jn">{{ $row['stepNo'] }}</span>{{ $row['langkahText'] }}</td>@endif
                                @if ($row['showBahaya'])<td class="jc-bahaya" rowspan="{{ $row['bahayaRowspan'] }}">@if($row['bahayaNo'] !== '')<span class="jn">{{ $row['bahayaNo'] }}</span>@endif{{ $row['bahayaText'] }}</td>@endif
                                <td class="jc-kendali">@if($row['kendaliNo'] !== '')<span class="jn">{{ $row['kendaliNo'] }}</span>@endif{{ $row['kendaliText'] }}{!! $anchor !!}</td>
                                <td class="jc-check">{!! $markBox($row) !!}</td>
                            </tr>
                        @endif
                    @empty
                        <tr><td class="jc-langkah"><span class="jn">1.</span></td><td colspan="3" style="text-align:center;color:#888">Belum ada analisa bahaya.</td></tr>
                    @endforelse
                </tbody>
            </table>

{{-- Footer: mengalir setelah isi → sekali saja, di halaman terakhir. --}}
<div class="page-footer">@include('documents.print._footer')</div>
</body>
</html>
