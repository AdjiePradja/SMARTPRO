@extends('layouts.app')
@section('title', 'Dokumen Revisi')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Dokumen Revisi</h1>
        <p class="text-muted small mb-0">Dokumen yang perlu Anda revisi: ditolak peninjau/approver, atau dokumen Berlaku yang diajukan revisi oleh SH/DH/PJO.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>No. Dokumen</th><th>Judul</th><th>Status</th><th style="min-width:220px">Feedback Peninjau</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            @php $annotations = $doc->reviews->flatMap->annotations; @endphp
                            <tr>
                                <td class="font-monospace small">{{ $doc->displayNumber() }}</td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td>
                                    @if ($doc->isRevisionDraft() && $doc->status === 'draft')
                                        <span class="badge bg-warning text-dark">Revisi Diajukan (Edisi {{ $doc->edisi }} Rev {{ $doc->no_revisi }})</span>
                                    @else
                                        <span class="badge bg-danger">{{ $doc->statusLabel() }}</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @forelse ($annotations->take(4) as $a)
                                        <div class="text-danger"><i class="bi bi-dot"></i> <span class="text-muted">[{{ $a->section_key }}]</span> {{ \Illuminate\Support\Str::limit($a->comment, 70) }}</div>
                                    @empty
                                        <span class="text-muted">Lihat komentar approver / peninjau di form revisi.</span>
                                    @endforelse
                                    @if ($annotations->count() > 4)<div class="text-muted">+{{ $annotations->count() - 4 }} lainnya…</div>@endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                                    <a href="{{ route('documents.edit', $doc) }}" class="btn btn-sm btn-warning"><i class="bi bi-arrow-counterclockwise"></i> Revisi</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-check2-circle fs-1 d-block mb-2 text-success"></i>Tidak ada dokumen yang perlu direvisi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($documents->hasPages())<div class="card-footer bg-white">{{ $documents->links() }}</div>@endif
    </div>
@endsection
