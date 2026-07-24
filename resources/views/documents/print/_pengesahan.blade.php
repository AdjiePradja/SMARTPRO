{{-- Halaman pengesahan schema-driven (D3): baris mengikuti approval_page_layout
     jenis dokumen. SOP = 3 baris (Dibuat/Ditinjau/Disetujui); SP & IK = 2 baris
     (Dibuat Oleh + "Ditinjau dan Disetujui Oleh" oleh satu SH/DH). --}}
@php
    $jabatanLabel = function ($user) {
        if (! $user) return '-';
        return match ($user->jabatan) {
            'pimpinan' => 'PJO',
            'section_head' => 'Section Head',
            'departemen_head' => 'Departemen Head',
            'group_leader' => 'Group Leader',
            'staff' => 'Non-Staff',   // label resmi jabatan staff (CLAUDE.md §6)
            default => $user->jabatan ?? '-',
        };
    };
    // Sudah pernah Berlaku (disetujui) → SELURUH TTD bercap APPROVED. Memakai
    // published_at agar dokumen "Sedang Direvisi"/obsolete yang dulu disahkan
    // tetap menampilkan capnya.
    $published = $document->published_at !== null;

    $reviewDate = optional($document->reviews->firstWhere('decision', 'approved'))->updated_at?->format('d/m/Y') ?? '-';
    $pubDate = $document->published_at?->format('d/m/Y') ?? '-';

    // Peta peran → [penandatangan, tanggal]. "peninjau_penyetuju" (SP/IK) = satu
    // SH/DH; tanggalnya = tgl terbit (persetujuan final), fallback tgl tinjau.
    $resolveRole = function ($role) use ($document, $reviewDate, $pubDate) {
        return match ($role) {
            'pembuat' => [$document->creator, $document->created_at?->format('d/m/Y') ?? '-'],
            'peninjau' => [$document->reviewer, $reviewDate],
            'penyetuju' => [$document->approver, $pubDate],
            'peninjau_penyetuju' => [$document->reviewer, $pubDate !== '-' ? $pubDate : $reviewDate],
            default => [null, '-'],
        };
    };

    $layoutRows = (isset($schema) ? ($schema->raw()['approval_page_layout']['rows'] ?? null) : null)
        ?? [
            ['role_label' => 'Dibuat Oleh', 'role' => 'pembuat'],
            ['role_label' => 'Ditinjau Oleh', 'role' => 'peninjau'],
            ['role_label' => 'Disetujui Oleh', 'role' => 'penyetuju'],
        ];

    // Pembuat TAMBAHAN (opsional) tampil sbg baris "Dibuat Oleh" ekstra TEPAT DI BAWAH
    // pembuat utama (#6) — boleh Non-Staff. Tanggalnya = tanggal dokumen dibuat.
    $extraAuthors = $document->authors()->where('is_primary', false)
        ->with('user')->get()->pluck('user')->filter()->values();

    $rows = [];
    foreach ($layoutRows as $r) {
        $role = $r['role'] ?? '';
        $label = $r['role_label'] ?? '';
        [$signer, $date] = $resolveRole($role);
        $rows[] = ['label' => $label, 'user' => $signer, 'date' => $date];

        if ($role === 'pembuat') {
            foreach ($extraAuthors as $extra) {
                $rows[] = ['label' => $label, 'user' => $extra, 'date' => $date];
            }
        }
    }
@endphp

<div class="section-bar">HALAMAN PENGESAHAN</div>
<table class="pengesahan">
    {{-- Lebar kolom PERSIS grid docx (3685|2106|2106|2632 twips = 35/20/20/25). --}}
    <thead>
        <tr><th style="width:35%">NAMA</th><th style="width:20%">JABATAN</th><th style="width:20%">TANGGAL</th><th style="width:25%">PENGESAHAN</th></tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td><span class="peng-role">{{ $row['label'] }}:</span><br><strong>{{ strtoupper($row['user']->name ?? '-') }}</strong></td>
                <td class="center">{{ $jabatanLabel($row['user']) }}</td>
                <td class="center">{{ $row['date'] }}</td>
                {{-- Saat dokumen Berlaku: SELURUH TTD bercap APPROVED (PNG, total ±45°),
                     di TENGAH kolom Pengesahan. Marker teks 1pt menjaga cap tetap
                     terbaca PrintLayoutTest dari stream PDF. --}}
                <td class="center" style="vertical-align:middle; text-align:center">
                    @if ($published && ($stamp ?? null))
                        <img src="{{ $stamp }}" alt="APPROVED" style="height:34pt; vertical-align:middle"><span style="font-size:1pt;color:#fff">APPROVED</span>
                    @elseif ($published)
                        <span class="stamp">APPROVED</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
