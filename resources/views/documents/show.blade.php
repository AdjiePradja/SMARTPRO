@extends('layouts.app')
@section('title', 'Detail: ' . $document->displayNumber())

@php
    $statusColors = [
        'draft' => 'secondary', 'waiting_for_review' => 'info', 'in_review' => 'primary',
        'rejected' => 'danger', 'pending_approval' => 'primary',
        'published' => 'success', 'sedang_direvisi' => 'warning', 'obsolete' => 'dark',
    ];
    $actionMeta = [
        'document.create' => ['Dokumen dibuat', 'bi-file-earmark-plus', 'primary'],
        'document.submit' => ['Dikirim untuk ditinjau', 'bi-send', 'info'],
        'document.withdraw' => ['Ditarik kembali ke draft', 'bi-arrow-counterclockwise', 'secondary'],
        'document.review_start' => ['Mulai ditinjau', 'bi-clipboard', 'info'],
        'document.review_approve' => ['Diloloskan peninjau', 'bi-check2', 'success'],
        'document.review_reject' => ['Dikembalikan untuk revisi', 'bi-arrow-counterclockwise', 'warning'],
        'document.approve' => ['Disetujui — Berlaku', 'bi-patch-check', 'success'],
        'document.approval_reject' => ['Ditolak approver', 'bi-x-circle', 'danger'],
        'document.cancel_revision' => ['Penolakan dibatalkan (ditinjau ulang)', 'bi-arrow-repeat', 'secondary'],
        'document.request_revision' => ['Revisi diajukan', 'bi-arrow-repeat', 'warning'],
        'document.cancel_revision_b' => ['Revisi dibatalkan', 'bi-x-circle', 'secondary'],
        'attachment.comment' => ['Komentar pada lampiran', 'bi-chat-left-text', 'info'],
    ];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ url()->previous() }}" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali</a>
            <h1 class="h5 fw-bold mb-0 mt-1">{{ $document->title }}</h1>
            <span class="font-monospace text-primary small">{{ $document->displayNumber() }}</span>
            <span class="badge bg-{{ $statusColors[$document->status] ?? 'secondary' }}">{{ $document->statusLabel() }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('documents.pdf', $document) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> Lihat PDF</a>
            <a href="{{ route('documents.edit', $document) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Buka Form</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-info-circle"></i> Informasi Dokumen</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Jenis</dt><dd class="col-7">{{ $document->type->name }}</dd>
                        <dt class="col-5 text-muted">Departemen</dt><dd class="col-7">{{ $document->department->name }}</dd>
                        <dt class="col-5 text-muted">No. Revisi</dt><dd class="col-7">{{ $document->no_revisi }}</dd>
                        <dt class="col-5 text-muted">Dibuat Oleh</dt><dd class="col-7">{{ $document->creator->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Ditinjau Oleh</dt><dd class="col-7">{{ $document->reviewer->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Disetujui Oleh</dt><dd class="col-7">{{ $document->approver->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Tgl Terbit</dt><dd class="col-7">{{ $document->published_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-clock-history"></i> Timeline Riwayat</div>
                <div class="card-body">
                    <div class="pp-timeline">
                        @forelse ($timeline as $log)
                            @php [$lbl, $icon, $clr] = $actionMeta[$log->action] ?? [str_replace(['document.', '_'], ['', ' '], $log->action), 'bi-dot', 'secondary']; @endphp
                            <div class="pp-timeline-item">
                                <span class="pp-timeline-dot bg-{{ $clr }}"><i class="bi {{ $icon }}"></i></span>
                                <div class="pp-timeline-content">
                                    <div class="fw-semibold small">{{ $lbl }}</div>
                                    <div class="text-muted" style="font-size:.75rem">
                                        {{ $log->user->name ?? 'Sistem' }} · {{ $log->created_at?->format('d/m/Y H:i') }} WITA
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">Belum ada riwayat.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .pp-timeline { position: relative; padding-left: .5rem; }
    .pp-timeline-item { position: relative; padding-left: 2.4rem; padding-bottom: 1.1rem; }
    .pp-timeline-item:not(:last-child)::before { content: ''; position: absolute; left: .85rem; top: 1.6rem; bottom: -.2rem; width: 2px; background: var(--bs-border-color); }
    .pp-timeline-dot { position: absolute; left: 0; top: 0; width: 1.75rem; height: 1.75rem; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-size: .8rem; }
</style>
@endpush
