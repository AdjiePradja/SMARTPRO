{{-- Kop JSA (Formulir SHE) — lebar kolom PERSIS ukuran cm dari pemilik (A4
     landscape): ikon 3.5cm | judul 18cm | meta 7cm = 12.3% / 63.2% / (9%+15.5%).
     No. Dokumen & Revisi otomatis; Tgl Efektif diisi pembuat. Berulang tiap
     halaman (kop-fixed di render-jsa). --}}
@php
    // No. Dokumen kop = nomor FORMULIR SHE (hardcode, tetap). Nomor dokumen kita
    // (PPA-ADRO-JSA-...) tampil di baris "No. Pekerjaan/JSA" pada blok info.
    $noForm = 'PPA-ADRO-F-SHE-03B';
    $revForm = (string) ($document->no_revisi ?? 0);
    // Tgl Efektif dikirim widget tanggal (YYYY-MM-DD) → tampilkan gaya Indonesia.
    $tglForm = $contentMap['form_tgl_efektif'] ?? '';
    $tglForm = is_string($tglForm) ? trim($tglForm) : '';
    if ($tglForm !== '') {
        try {
            $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $d = \Carbon\Carbon::parse($tglForm);
            $tglForm = $d->day.' '.$bulan[$d->month].' '.$d->year;
        } catch (\Throwable) {
            // bukan tanggal valid → tampilkan apa adanya
        }
    }
    $tglForm = $tglForm !== '' ? $tglForm : '-';
@endphp
<table class="kop">
    {{-- PENTING: lebar HARUS di sel baris pertama — DomPDF MENGABAIKAN colgroup
         (terukur dari garis border: tanpa ini kolom jadi rata 1/4). --}}
    <tr>
        <td class="kop-logo" rowspan="5" style="width:12.3%">
            @if($logo)<img src="{{ $logo }}" alt="PPA">@else<div class="kop-logo-ph">PPA</div>@endif
        </td>
        <td class="kop-title" rowspan="5" style="width:63.2%">FORMULIR JOB SAFETY ANALYSIS</td>
        <td class="mk" style="width:9%">No. Dokumen</td><td style="width:15.5%">{{ $noForm }}</td>
    </tr>
    <tr><td class="mk">Revisi</td><td>{{ $revForm }}</td></tr>
    <tr><td class="mk">Tgl Efektif</td><td>{{ $tglForm }}</td></tr>
    {{-- Nilai "X dari Y" di-stamp DomPDF (page_text) usai render; preview 1 dari 1. --}}
    <tr><td class="mk">Halaman</td><td>@if($screen ?? false)1 dari 1 @endif</td></tr>
    <tr><td class="mk">Edisi</td><td>{{ $document->edisi ?? 1 }}</td></tr>
</table>
