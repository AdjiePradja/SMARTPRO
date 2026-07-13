@extends('layouts.app')
@section('title', 'Status Dokumen Saya')

@php
    $statusColors = [
        'draft' => 'secondary', 'submitted' => 'info', 'in_review' => 'primary',
        'needs_revision' => 'warning', 'pending_approval' => 'primary',
        'published' => 'success', 'archived' => 'dark', 'obsolete' => 'dark',
    ];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">Status Dokumen Saya</h1>
            <p class="text-muted small mb-0">Dokumen yang Anda buat atau di departemen Anda.</p>
        </div>
        @can('document.create')
        <a href="{{ route('documents.create') }}" class="btn btn-pp"><i class="bi bi-file-earmark-plus"></i> Dokumen Baru</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Cari (nomor / judul)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. PPA-ADRO-SOP atau judul...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (\App\Models\Document::STATUS_LABELS as $val => $lbl)
                            @if (in_array($val, ['draft','in_review','rejected','pending_approval','published','sedang_direvisi','obsolete']))
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
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-sm btn-secondary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                    <a href="{{ route('documents.index') }}" class="btn btn-sm btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
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
                                <td class="font-monospace small">{{ $doc->doc_number }}</td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $doc->type->code }}</span></td>
                                <td><span class="badge bg-secondary">{{ $doc->department->code }}</span></td>
                                <td><span class="badge bg-{{ $statusColors[$doc->status] ?? 'secondary' }}">{{ $doc->statusLabel() }}</span></td>
                                <td class="small text-muted">{{ $doc->creator->name ?? '—' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                                    @if ($doc->status === 'draft')
                                        <a href="{{ route('documents.edit', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
                                        <form method="POST" action="{{ route('documents.submit', $doc) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success"><i class="bi bi-send"></i> Kirim</button>
                                        </form>
                                        <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="d-inline" onsubmit="return confirm('Hapus draft ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @else
                                        <a href="{{ route('documents.edit', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Lihat</a>
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
