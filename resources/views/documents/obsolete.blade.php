@extends('layouts.app')
@section('title', 'Dokumen Tidak Berlaku')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Dokumen Tidak Berlaku</h1>
        <p class="text-muted small mb-0"><i class="bi bi-slash-circle"></i> Dokumen yang sudah dinonaktifkan. Bisa dihapus permanen di sini.</p>
    </div>

    {{-- Filter & cari --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Cari (nomor / judul)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. PPA-ADRO-SOP atau judul...">
                </div>
                @if ($departments->isNotEmpty())
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Departemen</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($departments as $d)<option value="{{ $d->id }}" @selected(($filters['department_id'] ?? '') == $d->id)>{{ $d->code }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label small fw-semibold invisible d-block mb-1">.</label>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-input-h btn-secondary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ route('documents.obsolete') }}" class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
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
                        <tr><th>No. Dokumen</th><th>Judul</th><th>Jenis</th><th>Dept</th><th class="text-center">Edisi</th><th class="text-center">Revisi</th><th>Pembuat</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr>
                                <td class="font-monospace small">{{ $doc->displayNumber() }}</td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $doc->type->code }}</span></td>
                                <td><span class="badge bg-secondary">{{ $doc->department->code ?? '—' }}</span></td>
                                <td class="text-center small">{{ $doc->edisi ?? 1 }}</td>
                                <td class="text-center small">{{ $doc->no_revisi ?? 0 }}</td>
                                <td class="small">{{ $doc->creator->name ?? '—' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                                    <a href="{{ route('documents.show', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    {{-- Hapus = wewenang SH/DH/PJO/Admin; GL hanya melihat (v3 rev). --}}
                                    @can('document.request_revision')
                                    <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="d-inline"
                                          data-confirm="Hapus PERMANEN {{ $doc->displayNumber() }}? Tindakan ini tidak bisa dibatalkan."
                                          data-confirm-title="Hapus Permanen?" data-confirm-ok="Ya, hapus" data-confirm-icon="warning">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada dokumen tidak berlaku.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($documents->hasPages())<div class="card-footer bg-white">{{ $documents->links() }}</div>@endif
    </div>
@endsection
