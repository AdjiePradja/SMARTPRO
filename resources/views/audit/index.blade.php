@extends('layouts.app')
@section('title', 'Audit Log')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-body mb-0">Audit Log</h1>
        <p class="text-muted small mb-0">Jejak seluruh aksi penting (termasuk aksi admin) — waktu WITA.</p>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Cari user (nama / NRP)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Aksi (mis. approve, submit)</label>
                    <input type="text" name="action" value="{{ $filters['action'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button class="btn btn-sm btn-secondary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                    <a href="{{ route('audit.index') }}" class="btn btn-sm btn-light"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Waktu (WITA)</th><th>User</th><th>Aksi</th><th>Dokumen</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="small text-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                <td class="small">{{ $log->user->name ?? 'Sistem' }} <span class="text-muted">{{ $log->user->nrp ?? '' }}</span></td>
                                <td><span class="badge bg-light text-dark border font-monospace">{{ $log->action }}</span></td>
                                <td class="small font-monospace">
                                    @if ($log->document)<a href="{{ route('documents.show', $log->document) }}" class="text-decoration-none">{{ $log->document->displayNumber() }}</a>@else — @endif
                                </td>
                                <td class="small text-muted">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada catatan audit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($logs->hasPages())<div class="card-footer bg-white">{{ $logs->links() }}</div>@endif
    </div>
@endsection
