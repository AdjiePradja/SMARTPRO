@extends('layouts.app')
@section('title', 'Dokumen Baru')

@section('content')
    <div class="mb-3">
        <a href="{{ route('documents.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Kembali</a>
        <h1 class="h4 fw-bold text-dark mb-0 mt-1">Dokumen Baru {{ $type->code }} — Langkah 1</h1>
        <p class="text-muted small mb-0">Tentukan nomor, judul, dan departemen dokumen.</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif

                    <form method="POST" action="{{ route('documents.store') }}" x-data="{ manual: {{ old('doc_number_manual') ? 'true' : 'false' }} }">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jenis Dokumen</label>
                            <input type="text" class="form-control bg-light" value="{{ $type->code }} — {{ $type->name }}" readonly>
                            <input type="hidden" name="document_type_id" value="{{ $type->id }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Departemen</label>
                            @if ($canChooseDept)
                                <select name="department_id" class="form-select" required>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected(old('department_id', $defaultDept?->id) == $dept->id)>{{ $dept->code }} — {{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" value="{{ $defaultDept?->code }} — {{ $defaultDept?->name }}" readonly>
                                <input type="hidden" name="department_id" value="{{ $defaultDept?->id }}">
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Judul Dokumen</label>
                            <input type="text" name="title" value="{{ old('title') }}" class="form-control" required placeholder="mis. Prosedur Backup Data Server">
                        </div>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label small fw-semibold mb-0">Nomor Dokumen</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="doc_number_manual" :value="manual ? 1 : 0">
                                    <input class="form-check-input" type="checkbox" id="manualToggle" x-model="manual">
                                    <label for="manualToggle" class="form-check-label small">Input manual</label>
                                </div>
                            </div>

                            {{-- Auto (default): read-only preview --}}
                            <div x-show="!manual" x-cloak>
                                <input type="text" class="form-control bg-light" value="{{ $numberPreview }}" readonly>
                                <div class="form-text"><i class="bi bi-magic"></i> Nomor dibuat otomatis saat disimpan. Format: PPA-ADRO-JENIS-DEPT-NN.</div>
                            </div>

                            {{-- Manual: editable + uniqueness validated --}}
                            <div x-show="manual" x-cloak>
                                <input type="text" name="doc_number" value="{{ old('doc_number') }}" class="form-control" placeholder="mis. PPA-ADRO-SOP-ICTMD-05">
                                <div class="form-text">Pastikan nomor unik dan sesuai format.</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-pp"><i class="bi bi-arrow-right-circle"></i> Buat & Lanjut Pengisian</button>
                            <a href="{{ route('documents.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h2 class="h6 fw-bold text-secondary"><i class="bi bi-info-circle"></i> Tentang Penomoran</h2>
                    <p class="small text-muted mb-2">Nomor mengikuti pola tetap dan bertambah otomatis per jenis + departemen:</p>
                    <code class="d-block small mb-2">PPA-ADRO-<span class="text-primary">SOP</span>-<span class="text-success">ICTMD</span>-<span class="text-danger">01</span></code>
                    <p class="small text-muted mb-0">Aktifkan <strong>Input manual</strong> hanya bila perlu menyesuaikan nomor lama.</p>
                </div>
            </div>
        </div>
    </div>

    <style>[x-cloak]{display:none!important}</style>
@endsection
