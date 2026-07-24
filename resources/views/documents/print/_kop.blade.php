{{-- Kop SOP/IK/SP — lebar kolom PERSIS ukuran cm dari pemilik (A4 portrait):
     ikon 4cm | jenis+judul 8cm | meta 6cm = 22.2% / 44.5% / 33.3%. --}}
@php
    $raw = $schema->raw();
    $docTypeLabel = $raw['doc_type_label'] ?? strtoupper($document->type->name);
    $tglTerbit = $document->published_at?->format('d/m/Y') ?? '-';
    // Dokumen revisi = no_revisi > 0 ATAU hasil pengajuan revisi (roll-over edisi
    // membuat revisi kembali 0 — Edisi 2 Rev 0 tetap revisi, tgl revisi tampil).
    $isRevisi = $document->no_revisi > 0 || $document->revises_document_id;
    $tglRevisi = ($isRevisi && $document->updated_at) ? $document->updated_at->format('d/m/Y') : '-';
@endphp
<table class="kop">
    {{-- PENTING: lebar HARUS di sel baris pertama — DomPDF MENGABAIKAN colgroup
         (terukur dari garis border: tanpa ini kolom jadi rata 1/3). --}}
    <tr>
        <td class="kop-logo" rowspan="6" style="width:22.2%">
            @if($logo)<img src="{{ $logo }}" alt="PPA">@else<div class="kop-logo-ph">PPA</div>@endif
        </td>
        <td class="kop-title" rowspan="2" style="width:44.5%">{{ $docTypeLabel }}</td>
        <td class="kop-meta" style="width:33.3%">No. Dokumen: {{ $document->displayNumber() }}<br><span style="font-size:7pt;color:#444">@unless($document->hasFinalNumber())(sementara)@else&nbsp;@endunless</span></td>
    </tr>
    <tr><td class="kop-meta">No. Revisi: {{ $document->no_revisi }}</td></tr>
    <tr>
        <td class="kop-subject" rowspan="4">{{ strtoupper($document->title) }}</td>
        <td class="kop-meta">Edisi: {{ $document->edisi ?? 1 }}</td>
    </tr>
    {{-- PDF: sel dikosongkan — teks "Halaman: X dari Y" di-stamp DomPDF (page_text)
         usai render. Preview layar menampilkan 1 dari 1. --}}
    <tr><td class="kop-meta">@if($screen ?? false)Halaman: 1 dari 1 @endif</td></tr>
    <tr><td class="kop-meta">Tgl. Terbit: {{ $tglTerbit }}</td></tr>
    <tr><td class="kop-meta">Tgl. Revisi: {{ $tglRevisi }}</td></tr>
</table>
