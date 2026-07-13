{{-- Shared pengesahan page (D3). Only the PRIMARY author signs "Dibuat Oleh" (PRD v2 §2.3). --}}
@php
    $jabatanLabel = function ($user) {
        if (! $user) return '-';
        return match ($user->jabatan) {
            'pimpinan' => 'PJO',
            'section_head' => 'Section Head',
            'group_leader' => 'Group Leader',
            'staff' => 'Staff',
            default => $user->jabatan ?? '-',
        };
    };
    $rows = [
        ['label' => 'Dibuat Oleh',   'user' => $document->creator,  'date' => $document->created_at?->format('d/m/Y')],
        ['label' => 'Ditinjau Oleh', 'user' => $document->reviewer, 'date' => optional($document->reviews->firstWhere('decision', 'approved'))->updated_at?->format('d/m/Y') ?? '-'],
        ['label' => 'Disetujui Oleh','user' => $document->approver, 'date' => $document->published_at?->format('d/m/Y') ?? '-'],
    ];
    $published = $document->status === 'published';
@endphp

<div class="section-bar">HALAMAN PENGESAHAN</div>
<table class="pengesahan">
    <thead>
        <tr><th style="width:38%">NAMA</th><th style="width:22%">JABATAN</th><th style="width:18%">TANGGAL</th><th style="width:22%">PENGESAHAN</th></tr>
    </thead>
    <tbody>
        @foreach ($rows as $i => $row)
            <tr>
                <td><span class="peng-role">{{ $row['label'] }}:</span><br><strong>{{ strtoupper($row['user']->name ?? '-') }}</strong></td>
                <td class="center">{{ $jabatanLabel($row['user']) }}</td>
                <td class="center">{{ $row['date'] }}</td>
                <td class="center">
                    @if ($published && $i === 0)
                        <span class="stamp">APPROVED</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
