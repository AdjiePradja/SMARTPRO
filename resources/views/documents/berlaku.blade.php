@extends('layouts.app')
@section('title', 'Dokumen Berlaku')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Dokumen Berlaku</h1>
        <p class="text-muted small mb-0">Dokumen aktif {{ $departments->isNotEmpty() ? 'di 7 departemen' : 'di departemen Anda' }}. Ajukan Revisi untuk memperbarui (0→1).</p>
    </div>

    {{-- Filter & cari (2g) --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Cari (nomor / judul)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. PPA-ADRO-SOP atau judul...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Jenis</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (['SOP','IK','SP','JSA'] as $code)<option value="{{ $code }}" @selected(strtoupper($filters['type'] ?? '') === $code)>{{ $code }}</option>@endforeach
                    </select>
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
                    <label class="form-label small fw-semibold invisible d-block mb-1">.</label>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-input-h btn-secondary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ route('documents.published') }}" class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
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
                        <tr><th>No. Dokumen</th><th>Judul</th><th>Jenis</th><th>Dept</th><th>No. Revisi</th><th>Status</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr>
                                <td class="font-monospace small">{{ $doc->displayNumber() }}</td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $doc->type->code }}</span></td>
                                <td><span class="badge bg-secondary">{{ $doc->department->code }}</span></td>
                                <td class="text-center">{{ $doc->no_revisi }}</td>
                                <td>
                                    @if ($doc->status === 'published')
                                        <span class="badge bg-success">Berlaku</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Sedang Direvisi</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                                    @can('document.request_revision')
                                        @if ($doc->status === 'published')
                                            <form method="POST" action="{{ route('documents.requestRevision', $doc) }}" class="d-inline"
                                                  data-confirm="Buat Revisi ke-{{ $doc->no_revisi + 1 }} untuk {{ $doc->displayNumber() }}? Versi lama tetap Berlaku sementara sampai versi baru disetujui."
                                                  data-confirm-title="Ajukan Revisi?" data-confirm-ok="Ya, ajukan">
                                                @csrf
                                                <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-repeat"></i> Ajukan Revisi</button>
                                            </form>
                                            <form method="POST" action="{{ route('documents.makeObsolete', $doc) }}" class="d-inline"
                                                  data-confirm="Jadikan {{ $doc->displayNumber() }} TIDAK BERLAKU? Dokumen dipindah ke halaman Dokumen Tidak Berlaku dan bisa dihapus di sana."
                                                  data-confirm-title="Jadikan Tidak Berlaku?" data-confirm-ok="Ya, nonaktifkan" data-confirm-icon="warning">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-slash-circle"></i> Tidak Berlaku</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('documents.cancelRevisionB', $doc) }}" class="d-inline"
                                                  data-confirm="Batalkan revisi? Versi baru dibuang dan versi lama ({{ $doc->displayNumber() }}) kembali Berlaku."
                                                  data-confirm-title="Batalkan Revisi?" data-confirm-ok="Ya, batalkan">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Batalkan Revisi</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-folder2-open fs-1 d-block mb-2"></i>Belum ada dokumen Berlaku.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($documents->hasPages())<div class="card-footer bg-white">{{ $documents->links() }}</div>@endif
    </div>
@endsection
