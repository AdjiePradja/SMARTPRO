@extends('layouts.app')
@section('title', 'Dashboard')

@php
    $statusMeta = [
        'draft' => ['Draft', '#6c757d'],
        'in_review' => ['Dalam Peninjauan', '#0dcaf0'],
        'rejected' => ['Ditolak', '#dc3545'],
        'pending_approval' => ['Menunggu Persetujuan', '#0d6efd'],
        'published' => ['Berlaku', '#198754'],
        'sedang_direvisi' => ['Sedang Direvisi', '#ffc107'],
        'obsolete' => ['Obsolete', '#343a40'],
    ];
@endphp

@section('content')
    <div class="mb-4">
        <h1 class="h4 fw-bold text-body mb-1">Selamat datang, {{ $user->name }}</h1>
        <p class="text-muted mb-0">
            {{ \App\Models\User::JABATAN_LABELS[$user->jabatan] ?? ($user->getRoleNames()->first() ?? '-') }}
            @if($user->department) · {{ $user->department->code }} @endif
        </p>
    </div>

    {{-- Stat tiles --}}
    <div class="row g-3 mb-1">
        @php
            $tiles = [
                ['Total Dokumen', $stats['total'], 'bi-files', 'primary'],
                ['Dokumen Saya', $stats['my_documents'], 'bi-person-lines-fill', 'info'],
                ['Berlaku', $stats['published'], 'bi-patch-check', 'success'],
                ['Perlu Revisi', $stats['need_revision'], 'bi-arrow-counterclockwise', 'danger'],
            ];
        @endphp
        @foreach ($tiles as [$label, $value, $icon, $color])
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-{{ $color }} bg-opacity-10 p-3 me-3"><i class="bi {{ $icon }} fs-4 text-{{ $color }}"></i></div>
                        <div><div class="fs-3 fw-bold">{{ $value }}</div><div class="text-muted small">{{ $label }}</div></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Action queues (per role) --}}
    @if (!empty($queues))
        <div class="row g-3 mt-1">
            @isset($queues['review'])
                <div class="col-md-4"><a href="{{ route('review.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm border-start border-4 border-info"><div class="card-body d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clipboard-check text-info"></i> Perlu Ditinjau</span><span class="badge bg-info fs-6">{{ $queues['review'] }}</span>
                    </div></div></a></div>
            @endisset
            @isset($queues['approval'])
                <div class="col-md-4"><a href="{{ route('approvals.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm border-start border-4 border-primary"><div class="card-body d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-patch-check text-primary"></i> Perlu Disetujui</span><span class="badge bg-primary fs-6">{{ $queues['approval'] }}</span>
                    </div></div></a></div>
            @endisset
            @isset($queues['pending_users'])
                <div class="col-md-4"><a href="{{ route('users.pending') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm border-start border-4 border-warning"><div class="card-body d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-check text-warning"></i> Akun Menunggu</span><span class="badge bg-warning text-dark fs-6">{{ $queues['pending_users'] }}</span>
                    </div></div></a></div>
            @endisset
        </div>
    @endif

    <div class="row g-3 mt-1">
        {{-- Status chart --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent fw-bold"><i class="bi bi-pie-chart"></i> Distribusi Status Dokumen</div>
                <div class="card-body">
                    @if (array_sum($chart) === 0)
                        <div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Belum ada dokumen.</div>
                    @else
                        <canvas id="statusChart" height="220"></canvas>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent documents --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent fw-bold d-flex justify-content-between">
                    <span><i class="bi bi-clock-history"></i> Dokumen Terbaru</span>
                    <a href="{{ route('documents.index') }}" class="small text-decoration-none">Lihat semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <tbody>
                                @forelse ($recent as $doc)
                                    <tr>
                                        <td class="font-monospace small ps-3">{{ $doc->doc_number }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($doc->title, 34) }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $doc->type->code }}</span></td>
                                        <td>@php [$lbl,$clr] = $statusMeta[$doc->status] ?? [$doc->statusLabel(),'#6c757d']; @endphp
                                            <span class="badge" style="background:{{ $clr }}">{{ $lbl }}</span></td>
                                        <td class="text-end pe-3"><a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a></td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-muted py-4">Belum ada dokumen.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@if (array_sum($chart) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const meta = @json($statusMeta);
        const data = @json($chart);
        const labels = [], values = [], colors = [];
        Object.keys(data).forEach(k => { if (data[k] > 0) { labels.push(meta[k][0]); values.push(data[k]); colors.push(meta[k][1]); } });
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }] },
            options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
        });
    })();
</script>
@endif
@endpush
