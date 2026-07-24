@extends('layouts.app')
@section('title', 'Dashboard')

@php
    // Palet status — HANYA 4 warna Soft UI (Users/Clicks/Sales/Items) + netral.
    $statusMeta = [
        'draft' => ['Draft', '#8392ab'],
        'in_review' => ['Dalam Peninjauan', '#0ea5e9'],          // biru (Clicks)
        'rejected' => ['Ditolak', '#ef4444'],                    // pink (Items)
        'pending_approval' => ['Menunggu Persetujuan', '#f97316'], // kuning-oranye (Sales)
        'published' => ['Berlaku', '#ea580c'],                   // oranye (Users)
        'sedang_direvisi' => ['Sedang Direvisi', '#eab308'],     // kuning (Sales)
        'obsolete' => ['Tidak Berlaku', '#344767'],
    ];
    $actionMeta = [
        'document.create' => ['membuat dokumen', 'bi-file-earmark-plus', 'bg-su-orange'],
        'document.submit' => ['mengirim untuk ditinjau', 'bi-send', 'bg-su-blue'],
        'document.withdraw' => ['menarik dokumen', 'bi-arrow-counterclockwise', 'bg-su-yellow'],
        'document.review_start' => ['mulai meninjau', 'bi-clipboard', 'bg-su-blue'],
        'document.review_approve' => ['meloloskan tinjauan', 'bi-check2', 'bg-su-orange'],
        'document.review_reject' => ['mengembalikan untuk revisi', 'bi-arrow-counterclockwise', 'bg-su-yellow'],
        'document.approve' => ['menyetujui (Berlaku)', 'bi-patch-check', 'bg-su-orange'],
        'document.approval_reject' => ['menolak (approver)', 'bi-x-circle', 'bg-su-pink'],
        'document.cancel_revision' => ['membatalkan penolakan', 'bi-arrow-repeat', 'bg-su-blue'],
        'document.request_revision' => ['mengajukan revisi', 'bi-arrow-repeat', 'bg-su-yellow'],
        'document.cancel_revision_b' => ['membatalkan revisi', 'bi-x-circle', 'bg-su-blue'],
        'document.delete' => ['menghapus draft', 'bi-trash', 'bg-su-pink'],
        'attachment.comment' => ['mengomentari lampiran', 'bi-chat-left-text', 'bg-su-blue'],
        'user.login' => ['masuk', 'bi-box-arrow-in-right', 'bg-su-blue'],
    ];
@endphp

@section('content')
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="h4 fw-bold text-body mb-1">{{ $greeting }}, {{ $user->name }} — selamat bekerja.</h1>
            <p class="text-muted mb-0">
                {{ \App\Models\User::JABATAN_LABELS[$user->jabatan] ?? ($user->getRoleNames()->first() ?? '-') }}
                @if($user->department) · {{ $user->department->code }} @endif
            </p>
        </div>
        <div class="text-muted small text-end"><i class="bi bi-clock"></i> {{ now()->format('l, d M Y · H:i') }} WITA</div>
    </div>

    {{-- ROW 1: Dokumen Overview (kiri) + 4 kartu 2x2 (kanan) --}}
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-0">Dokumen Overview</h6>
                    @php $totalCreated = collect($monthly)->sum('count'); @endphp
                    <p class="text-sm text-muted mb-3">
                        <i class="bi bi-arrow-up text-su-orange fw-bold"></i>
                        <span class="fw-bold text-body">{{ $totalCreated }} dokumen</span> dibuat dalam 8 bulan terakhir
                    </p>
                    <div style="height:280px"><canvas id="overviewChart"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="row g-3 h-100">
                @php
                    // Elemen ke-5 = URL (opsional): kartu jadi tautan ke daftar terkait.
                    $isGl = ($isCreator ?? false);   // group_leader
                    // Kartu ke-4 tergantung peran: GL merevisi → "Perlu Revisi";
                    // SH/DH/PJO tidak merevisi → "Sedang Revisi" (#6).
                    $revTile = $isGl
                        ? ['Perlu Revisi', $stats['need_revision'], 'bi-arrow-counterclockwise', 'bg-su-pink', route('documents.revisi')]
                        : ['Sedang Revisi', $stats['sedang_direvisi'], 'bi-arrow-repeat', 'bg-su-pink', null];
                    // Kartu 1 "Total Dokumen": GL = seluruh dokumen DEPT-nya (klik → Dokumen
                    // Departemen); lainnya = total yg terlihat, tanpa tautan (#2).
                    $totalTile = $isGl
                        ? ['Total Dokumen', $stats['dept_documents'], 'bi-files', 'bg-su-orange', route('documents.staffStatus')]
                        : ['Total Dokumen', $stats['total'], 'bi-files', 'bg-su-orange', null];
                    // Kartu ke-2: SH/DH = dokumen se-departemen (klik → Status Dokumen Staff);
                    // GL = "Dokumen Saya" buatannya (klik → Dokumen Saya, #2/#4).
                    $secondTile = ($isDeptHead ?? false)
                        ? ['Dokumen Dept', $stats['dept_documents'], 'bi-building', 'bg-su-blue', route('documents.staffStatus')]
                        : ['Dokumen Saya', $stats['my_documents'], 'bi-person-lines-fill', 'bg-su-blue', $isGl ? route('documents.index') : null];
                    $tiles = [
                        $totalTile,
                        $secondTile,
                        ['Berlaku', $stats['published'], 'bi-patch-check', 'bg-su-yellow', null],
                        $revTile,
                    ];
                @endphp
                @foreach ($tiles as [$label, $value, $icon, $bg, $url])
                    <div class="col-6">
                        @if ($url)<a href="{{ $url }}" class="text-decoration-none d-block h-100">@endif
                        <div class="card border-0 shadow-sm {{ $bg }} h-100{{ $url ? ' pp-tile-link' : '' }}">
                            <div class="card-body p-3">
                                <div class="bg-white rounded-circle shadow-sm mb-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                                    <i class="bi {{ $icon }}" style="font-size:1.1rem;line-height:1;color:#344767"></i>
                                </div>
                                <h3 class="text-white font-weight-bolder mb-0">{{ $value }}</h3>
                                <span class="text-white text-sm" style="opacity:.9">{{ $label }}@if ($url)<i class="bi bi-arrow-right-short"></i>@endif</span>
                            </div>
                        </div>
                        @if ($url)</a>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ROW 2: (kiri) kartu antrian/aksi + Distribusi Dokumen · (kanan) Log Aktivitas.
         Kartu antrian dipindah ke ATAS Distribusi (dlm col-8) supaya kolom kiri lebih
         tinggi & Log Aktivitas kanan (h-100) memenuhi tinggi → menutup celah (#5/#7).
         GL memperoleh kartu "Dokumen Ditolak" di samping "Akun Menunggu" (#5). --}}
    @php
        $actionCards = [];
        if (isset($queues['review'])) $actionCards[] = ['Perlu Ditinjau', $queues['review'], route('review.index'), 'bi-clipboard-check', 'text-su-blue', 'bg-su-blue'];
        if (isset($queues['approval'])) $actionCards[] = ['Perlu Disetujui', $queues['approval'], route('approvals.index'), 'bi-patch-check', 'text-su-orange', 'bg-su-orange'];
        if (isset($queues['pending_users'])) $actionCards[] = ['Akun Menunggu', $queues['pending_users'], route('users.pending'), 'bi-person-check', 'text-su-yellow', 'bg-su-yellow'];
        if ($isCreator ?? false) $actionCards[] = ['Dokumen Ditolak', $stats['rejected'], route('documents.revisi'), 'bi-x-octagon', 'text-danger', 'bg-su-pink'];
        $acCol = count($actionCards) >= 3 ? 'col-sm-6 col-xl-4' : 'col-sm-6';
    @endphp
    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            @if ($actionCards)
                <div class="row g-3 mb-3">
                    @foreach ($actionCards as [$lbl, $val, $url, $icon, $txt, $bg])
                        <div class="{{ $acCol }}"><a href="{{ $url }}" class="text-decoration-none">
                            <div class="card border-0 shadow-sm"><div class="card-body d-flex justify-content-between align-items-center py-3">
                                <span class="text-body"><i class="bi {{ $icon }} {{ $txt }}"></i> {{ $lbl }}</span><span class="badge {{ $bg }}">{{ $val }}</span>
                            </div></div></a></div>
                    @endforeach
                </div>
            @endif
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent pb-0">
                    <h6 class="fw-bold mb-0">Distribusi Dokumen</h6>
                    <p class="text-sm text-muted mb-0">Jumlah dokumen per status &amp; jenis</p>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    @foreach ($jenisList as $j)<th class="text-center">{{ $j }}</th>@endforeach
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($matrix as $row)
                                    @php [$lbl, $clr] = $statusMeta[$row['status']] ?? [$row['label'], '#8392ab']; @endphp
                                    <tr>
                                        <td>
                                            <span class="d-inline-block rounded-circle me-2 align-middle" style="width:9px;height:9px;background:{{ $clr }}"></span>
                                            <span class="fw-semibold text-sm">{{ $lbl }}</span>
                                        </td>
                                        @foreach ($jenisList as $j)
                                            <td class="text-center text-sm {{ $row['per'][$j] === 0 ? 'text-muted' : 'fw-semibold' }}">{{ $row['per'][$j] }}</td>
                                        @endforeach
                                        <td class="text-center fw-bold">{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Log Aktivitas gaya "Orders overview": timeline vertikal + ikon berwarna --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent pb-0">
                    <h6 class="fw-bold mb-0">Log Aktivitas</h6>
                    <p class="text-sm text-muted mb-0"><i class="bi bi-clock-history"></i> aktivitas terbaru</p>
                </div>
                <div class="card-body">
                    <div class="pp-timeline">
                        @forelse ($activities as $log)
                            @php [$aksi, $icon, $bg] = $actionMeta[$log->action] ?? [str_replace(['document.', '_'], ['', ' '], $log->action), 'bi-dot', 'bg-su-blue']; @endphp
                            <div class="pp-tl-item">
                                <span class="pp-tl-icon {{ $bg }}"><i class="bi {{ $icon }}"></i></span>
                                <div class="pp-tl-body">
                                    <div class="text-sm"><span class="fw-semibold">{{ $log->user->name ?? 'Sistem' }}</span> {{ $aksi }}
                                        @if ($log->document)<a href="{{ route('documents.show', $log->document) }}" class="text-decoration-none font-monospace text-xs">{{ $log->document->displayNumber() }}</a>@endif
                                    </div>
                                    <div class="text-xs text-muted">{{ $log->created_at?->format('d M, H:i') }} WITA</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Belum ada aktivitas.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')<style>
    .text-su-orange { color: #ea580c !important; }
    .text-su-blue { color: #0ea5e9 !important; }
    .text-su-yellow { color: #f97316 !important; }
    /* Timeline gaya Orders overview */
    .pp-timeline { position: relative; }
    .pp-tl-item { position: relative; display: flex; gap: .75rem; padding-bottom: 1.1rem; }
    .pp-tl-item:not(:last-child)::before { content: ''; position: absolute; left: 15px; top: 30px; bottom: 0; width: 2px; background: #eef1f6; }
    .pp-tl-icon { flex: 0 0 auto; width: 30px; height: 30px; border-radius: .6rem; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .8rem; box-shadow: 0 2px 5px rgba(0,0,0,.12); }
    .pp-tl-body { padding-top: 2px; }
    [data-bs-theme="dark"] .pp-tl-item:not(:last-child)::before { background: #2b3138; }
    /* Kartu statistik yang jadi tautan (#2): sedikit terangkat saat hover. */
    .pp-tile-link { cursor: pointer; transition: transform .15s ease, box-shadow .15s ease; }
    .pp-tile-link:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.18) !important; }
</style>@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const el = document.getElementById('overviewChart');
        if (!el) return;
        const labels = @json(array_column($monthly, 'label'));
        const values = @json(array_column($monthly, 'count'));
        const ctx = el.getContext('2d');
        // Gradient lembut di bawah garis (gaya Sales overview Soft UI)
        const grad = ctx.createLinearGradient(0, 0, 0, 280);
        grad.addColorStop(0, 'rgba(234,88,12,.30)');
        grad.addColorStop(1, 'rgba(234,88,12,0)');
        new Chart(ctx, {
            type: 'line',
            data: { labels, datasets: [{
                label: 'Dokumen dibuat', data: values,
                borderColor: '#ea580c', borderWidth: 2,
                backgroundColor: grad, fill: true,
                tension: 0.4, pointRadius: 0, pointHoverRadius: 5,
                pointHoverBackgroundColor: '#ea580c', pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
            }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: {
                    backgroundColor: '#344767', titleColor: '#fff', bodyColor: '#fff', padding: 10, displayColors: false,
                    callbacks: { label: c => ` ${c.parsed.y} dokumen` }
                } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, color: '#9aa4b2', font: { size: 11 } }, grid: { color: 'rgba(0,0,0,.05)', borderDash: [4, 4], drawBorder: false } },
                    x: { ticks: { color: '#9aa4b2', font: { size: 11 } }, grid: { display: false, drawBorder: false } }
                }
            }
        });
    })();
</script>
@endpush
