{{-- signature: choose reviewer (Ditinjau) + approver (Disetujui). Creator is fixed. --}}
<div class="mb-4">
    <label class="form-label fw-semibold">{{ $section['label'] ?? 'Pengesahan' }}</label>
    @isset($section['help'])<div class="form-text mb-3">{{ $section['help'] }}</div>@endisset

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Dibuat Oleh</label>
            <input type="text" class="form-control bg-light" value="{{ $document->creator->name ?? '—' }}" readonly>
            <div class="form-text">{{ $document->creator->jabatan ?? '' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Ditinjau Oleh (Peninjau)</label>
            <select name="reviewer_id" class="form-select">
                <option value="">— Pilih Peninjau —</option>
                @foreach ($reviewers as $r)
                    <option value="{{ $r->id }}" @selected($document->reviewer_id == $r->id)>{{ $r->name }} ({{ $r->jabatan ?? $r->getRoleNames()->first() }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Disetujui Oleh (Pimpinan)</label>
            <select name="approver_id" class="form-select">
                <option value="">— Pilih Pimpinan —</option>
                @foreach ($approvers as $a)
                    <option value="{{ $a->id }}" @selected($document->approver_id == $a->id)>{{ $a->name }} ({{ $a->jabatan ?? 'PJO' }})</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="alert alert-light border small mt-3 mb-0">
        <i class="bi bi-info-circle"></i> Peninjau &amp; Pimpinan dipilih di sini. Pengajuan ke review dibangun pada Fase 4.
    </div>
</div>
