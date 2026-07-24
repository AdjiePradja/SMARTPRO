@extends('layouts.app')
@php
    // GL (pembuat, tanpa wewenang tinjau/lihat-semua) memakai daftar ini sbg
    // "Dokumen Departemen"; SH/DH/PJO sbg "Status Dokumen Staff".
    $isGlDept = auth()->user()->can('document.create')
        && ! auth()->user()->can('document.review') && ! $canAll;
    $pageTitle = $isGlDept ? 'Dokumen Departemen' : 'Status Dokumen Staff';
@endphp
@section('title', $pageTitle)

@php
    $statusColors = [
        'draft' => 'secondary', 'waiting_for_review' => 'info', 'in_review' => 'primary',
        'rejected' => 'danger', 'pending_approval' => 'primary',
        'published' => 'success', 'sedang_direvisi' => 'warning', 'obsolete' => 'dark',
    ];
@endphp

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-body mb-0">{{ $pageTitle }}</h1>
        <p class="text-muted small mb-0">
            <i class="bi bi-eye"></i> Read-only — {{ $isGlDept ? 'seluruh dokumen' : 'pantau status dokumen' }}
            @if ($canAll)
                @if ($selectedDept)di departemen <strong>{{ $selectedDept->code }}</strong>@else di seluruh departemen (pilih dept di sub-menu)@endif
            @else
                di departemen Anda
            @endif.
        </p>
    </div>

    {{-- Filter & cari --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                @if ($canAll)<input type="hidden" name="department_id" value="{{ $filters['department_id'] ?? '' }}">@endif
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Cari (nomor / judul)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. PPA-ADRO-SOP atau judul...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Jenis</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($types as $code)<option value="{{ $code }}" @selected(strtoupper($filters['type'] ?? '') === $code)>{{ $code }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
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
                    <label class="form-label small fw-semibold invisible d-block mb-1">.</label>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-input-h btn-secondary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ route('documents.staffStatus', $canAll && ($filters['department_id'] ?? null) ? ['department_id' => $filters['department_id']] : []) }}" class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
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
                        <tr><th>No. Dokumen</th><th>Judul</th><th>Jenis</th><th>Status</th><th>Pembuat</th><th class="text-end">Lihat</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr>
                                <td class="font-monospace small">{{ $doc->displayNumber() }}</td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $doc->type->code }}</span></td>
                                <td><span class="badge bg-{{ $statusColors[$doc->status] ?? 'secondary' }}">{{ $doc->statusLabel() }}</span></td>
                                <td class="small">{{ $doc->creator->name ?? '—' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                                    <a href="{{ route('documents.show', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Lihat</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>@if ($canAll && ! $selectedDept)Pilih departemen di sub-menu untuk melihat dokumennya.@else Belum ada dokumen sesuai filter.@endif</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($documents->hasPages())<div class="card-footer bg-white">{{ $documents->links() }}</div>@endif
    </div>
@endsection
