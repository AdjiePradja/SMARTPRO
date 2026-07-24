@extends('layouts.app')
@section('title', 'Persetujuan Saya')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Persetujuan Saya</h1>
        <p class="text-muted small mb-0">Dokumen yang lolos tinjauan dan menunggu persetujuan Anda.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>No. Dokumen</th><th>Judul</th><th>Dept</th><th>Pembuat</th><th>Peninjau</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr>
                                <td class="font-monospace small">{{ $doc->displayNumber() }}</td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td><span class="badge bg-secondary">{{ $doc->department->code }}</span></td>
                                <td class="small">{{ $doc->creator->name ?? '—' }}</td>
                                <td class="small">{{ $doc->reviewer->name ?? '—' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                                    <a href="{{ route('approvals.show', $doc) }}" class="btn btn-sm btn-primary"><i class="bi bi-patch-check"></i> Tinjau &amp; Setujui</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada dokumen yang menunggu persetujuan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($documents->hasPages())<div class="card-footer bg-white">{{ $documents->links() }}</div>@endif
    </div>
@endsection
