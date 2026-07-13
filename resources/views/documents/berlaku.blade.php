@extends('layouts.app')
@section('title', 'Dokumen Berlaku')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Dokumen Berlaku</h1>
        <p class="text-muted small mb-0">Dokumen aktif di departemen. Ajukan Revisi untuk memperbarui (0→1).</p>
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
                                <td class="font-monospace small">{{ $doc->doc_number }}</td>
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
                                                  onsubmit="return confirm('Ajukan revisi untuk {{ $doc->doc_number }}? Versi baru (Revisi {{ $doc->no_revisi + 1 }}) akan dibuat.')">
                                                @csrf
                                                <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-repeat"></i> Ajukan Revisi</button>
                                            </form>
                                        @else
                                            <span class="badge bg-light text-muted border">Revisi sedang berjalan</span>
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
