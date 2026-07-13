{{-- Shared kop (D3) — repeats on every page. Matches SOP-contoh.pdf proportions. --}}
@php
    $raw = $schema->raw();
    $docTypeLabel = $raw['doc_type_label'] ?? strtoupper($document->type->name);
    $tglTerbit = $document->published_at?->format('d/m/Y') ?? '-';
    $tglRevisi = ($document->no_revisi > 0 && $document->updated_at) ? $document->updated_at->format('d/m/Y') : '-';
@endphp
<table class="kop">
    <colgroup>
        <col style="width:24%">
        <col style="width:41%">
        <col style="width:35%">
    </colgroup>
    <tr>
        <td class="kop-logo" rowspan="5">
            @if($logo)<img src="{{ $logo }}" alt="PPA">@else<div class="kop-logo-ph">PPA</div>@endif
        </td>
        <td class="kop-title" rowspan="2">{{ $docTypeLabel }}</td>
        <td class="kop-meta">No. Dokumen: {{ $document->doc_number }}</td>
    </tr>
    <tr><td class="kop-meta">No. Revisi: {{ $document->no_revisi }}</td></tr>
    <tr>
        <td class="kop-subject" rowspan="3">{{ strtoupper($document->title) }}</td>
        <td class="kop-meta">Edisi: {{ $document->edisi ?? 1 }}</td>
    </tr>
    <tr><td class="kop-meta">Tgl. Terbit: {{ $tglTerbit }}</td></tr>
    <tr><td class="kop-meta">Tgl. Revisi: {{ $tglRevisi }}</td></tr>
</table>
