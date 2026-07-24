@extends('layouts.app')
@php $isMine = ($showAllStatuses ?? false); @endphp
@section('title', $isMine ? 'Dokumen Saya' : 'Status Dokumen')

@php
    $statusColors = [
        'draft' => 'secondary', 'submitted' => 'info', 'waiting_for_review' => 'info',
        'in_review' => 'primary', 'rejected' => 'danger',
        'needs_revision' => 'warning', 'pending_approval' => 'primary',
        'published' => 'success', 'archived' => 'dark', 'obsolete' => 'dark',
    ];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">{{ $isMine ? 'Dokumen Saya' : 'Status Dokumen' }}</h1>
            <p class="text-muted small mb-0">{{ $isMine ? 'Seluruh dokumen yang Anda buat (segala status).' : 'Dokumen yang Anda buat atau di departemen Anda.' }}</p>
        </div>
        @can('document.create')
        <a href="{{ route('documents.create') }}" class="btn btn-pp"><i class="bi bi-file-earmark-plus"></i> Dokumen Baru</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Cari (nomor / judul)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. PPA-ADRO-SOP atau judul...">
                </div>
                @if ($departments->isNotEmpty())
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Departemen</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($departments as $d)<option value="{{ $d->id }}" @selected(($filters['department_id'] ?? '') == $d->id)>{{ $d->code }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        {{-- GL "Dokumen Saya" = segala status; lainnya hanya berproses
                             (Berlaku ada di menu Dokumen Berlaku). --}}
                        @php $statusOpts = ($showAllStatuses ?? false)
                            ? ['draft','waiting_for_review','in_review','rejected','pending_approval','published']
                            : ['draft','waiting_for_review','in_review','rejected','pending_approval']; @endphp
                        @foreach (\App\Models\Document::STATUS_LABELS as $val => $lbl)
                            @if (in_array($val, $statusOpts))
                                <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $lbl }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Jenis</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($types as $code)<option value="{{ $code }}" @selected(($filters['type'] ?? '') === $code)>{{ $code }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold invisible d-block mb-1">.</label>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-input-h btn-secondary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ route('documents.index') }}" class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>No. Dokumen</th><th>Judul</th><th>Jenis</th><th>Dept</th><th>Status</th><th>Pembuat</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr>
                                <td class="font-monospace small">{{ $doc->displayNumber() }}</td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $doc->type->code }}</span></td>
                                <td><span class="badge bg-secondary">{{ $doc->department->code }}</span></td>
                                <td><span class="badge bg-{{ $statusColors[$doc->status] ?? 'secondary' }}">{{ $doc->statusLabel() }}</span></td>
                                <td class="small text-muted">{{ $doc->creator->name ?? '—' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                                    @php
                                        // Aksi kelola HANYA untuk pemilik yang berhak membuat/menyunting
                                        // (GL/Admin). Non-Staff & non-pemilik = read-only.
                                        $mine = $doc->created_by === auth()->id() && auth()->user()->can('document.create');
                                    @endphp
                                    @if ($mine && $doc->status === 'draft')
                                        <a href="{{ route('documents.edit', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
                                        <form method="POST" action="{{ route('documents.submit', $doc) }}" class="d-inline"
                                              data-confirm="Kirim dokumen ini untuk ditinjau? Setelah dikirim tidak bisa diedit (masih bisa Ditarik selama belum ditinjau)."
                                              data-confirm-title="Kirim Dokumen?" data-confirm-icon="warning" data-confirm-ok="Ya, kirim">
                                            @csrf
                                            <button class="btn btn-sm btn-success"><i class="bi bi-send"></i> Kirim</button>
                                        </form>
                                        <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="d-inline"
                                              data-confirm="Hapus draft ini? Tindakan tidak bisa dibatalkan." data-confirm-title="Hapus Draft?" data-confirm-icon="warning" data-confirm-ok="Ya, hapus">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @elseif ($mine && $doc->status === 'waiting_for_review')
                                        <a href="{{ route('documents.show', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Lihat</a>
                                        <form method="POST" action="{{ route('documents.withdraw', $doc) }}" class="d-inline"
                                              data-confirm="Tarik dokumen dari antrian tinjauan? Dokumen kembali ke Draft." data-confirm-title="Tarik Dokumen?" data-confirm-ok="Ya, tarik">
                                            @csrf
                                            <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-counterclockwise"></i> Tarik</button>
                                        </form>
                                    @else
                                        <a href="{{ route('documents.show', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Lihat</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada dokumen. @can('document.create')<a href="{{ route('documents.create') }}">Buat dokumen baru</a>.@endcan
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($documents->hasPages())
            <div class="card-footer bg-white">{{ $documents->links() }}</div>
        @endif
    </div>
@endsection
