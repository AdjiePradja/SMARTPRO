@extends('layouts.app')
@section('title', 'Persetujuan: ' . $document->displayNumber())

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ route('approvals.index') }}" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke antrian</a>
            <h1 class="h5 fw-bold mb-0 mt-1">{{ $document->title }}</h1>
            <span class="font-monospace text-primary small">{{ $document->displayNumber() }}</span>
            <span class="badge bg-secondary">{{ $document->department->code }}</span>
        </div>
        <a href="{{ route('documents.pdf', $document) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> Lihat PDF</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-people"></i> Rantai Dokumen</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4">Dibuat Oleh</dt><dd class="col-8">{{ $document->creator->name ?? '—' }} ({{ $document->creator->nrp ?? '' }})</dd>
                        <dt class="col-4">Ditinjau Oleh</dt><dd class="col-8">{{ $document->reviewer->name ?? '—' }}</dd>
                        <dt class="col-4">Departemen</dt><dd class="col-8">{{ $document->department->name }}</dd>
                        <dt class="col-4">Edisi / Revisi</dt><dd class="col-8">Edisi {{ $document->edisi }} · Revisi {{ $document->no_revisi }}</dd>
                    </dl>
                    <p class="text-muted small mt-3 mb-0"><i class="bi bi-info-circle"></i> Tinjau isi lengkap lewat tombol <strong>Lihat PDF</strong>. Penilaian per-item sudah dilakukan peninjau.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-patch-check"></i> Keputusan</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('approvals.store', $document) }}">
                        @csrf
                        <label class="form-label small fw-semibold">Catatan / Alasan <span class="text-muted">(wajib bila mengajukan revisi)</span></label>
                        <textarea name="summary" class="form-control mb-2" rows="3" placeholder="Alasan revisi / catatan persetujuan...">{{ old('summary') }}</textarea>
                        @error('summary')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        <div class="form-text mb-3">Catatan ini tampil di form revisi pembuat.</div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="decision" value="approve" class="btn btn-success"
                                    data-confirm="Setujui dokumen ini menjadi Berlaku? Seluruh tanda tangan akan bercap APPROVED."
                                    data-confirm-title="Setujui Dokumen?" data-confirm-ok="Ya, setujui"><i class="bi bi-check-circle"></i> Setujui (Berlaku)</button>
                            <button type="submit" name="decision" value="reject" class="btn btn-outline-danger"
                                    data-confirm="Kembalikan dokumen ke pembuat untuk revisi? Pastikan alasan/catatan sudah diisi."
                                    data-confirm-title="Kembalikan untuk Revisi?" data-confirm-icon="warning" data-confirm-ok="Ya, kembalikan"><i class="bi bi-arrow-counterclockwise"></i> Kembalikan untuk Revisi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
