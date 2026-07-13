@extends('layouts.app')
@section('title', 'Tinjau: ' . $document->doc_number)

@php
    // Only content sections are reviewed (skip user_picker verification fields).
    $reviewTypes = ['rich_list', 'reference_picker', 'repeatable_group'];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ route('review.index') }}" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke antrian</a>
            <h1 class="h5 fw-bold mb-0 mt-1">{{ $document->title }}</h1>
            <span class="font-monospace text-primary small">{{ $document->doc_number }}</span>
            <span class="badge bg-secondary">{{ $document->department->code }}</span>
            <span class="badge bg-info-subtle text-info-emphasis">Revisi ke-{{ $document->revision_round }}</span>
        </div>
        <a href="{{ route('documents.pdf', $document) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> Lihat PDF</a>
    </div>

    <div class="alert alert-info small"><i class="bi bi-info-circle"></i> Beri catatan pada item yang perlu diperbaiki. Kosongkan bila item sudah sesuai. Bila ada satu saja catatan, pilih <strong>Kembalikan untuk Revisi</strong>.</div>

    {{-- AI Review Assist (§9). AI hanya membantu; keputusan tetap di peninjau. --}}
    <div class="card border-0 shadow-sm mb-3" x-data="aiReview('{{ route('review.ai', $document) }}', '{{ csrf_token() }}')">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-robot"></i> AI Review Assist <span class="text-muted small fw-normal">— saran, bukan keputusan</span></span>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="analyze()" :disabled="loading">
                    <span x-show="!loading"><i class="bi bi-stars"></i> Analisis dengan AI</span>
                    <span x-show="loading" x-cloak><span class="spinner-border spinner-border-sm"></span> Menganalisis…</span>
                </button>
            </div>
            <template x-if="summary"><div class="alert alert-light border mt-3 mb-2 small"><strong>Ringkasan AI:</strong> <span x-text="summary"></span></div></template>
            <template x-if="findings.length">
                <div class="mt-2">
                    <div class="small text-muted mb-2">Temuan — <strong>Adopsi</strong> untuk memasukkan ke catatan (boleh diedit dulu), atau <strong>Tolak</strong>:</div>
                    <template x-for="(f, i) in findings" :key="i">
                        <div class="border rounded p-2 mb-2 bg-body-tertiary">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-secondary" x-text="f.section_key"></span>
                                <span class="badge" :class="sevClass(f.severity)" x-text="f.severity"></span>
                            </div>
                            <div class="small mt-1"><strong>Masalah:</strong> <span x-text="f.issue"></span></div>
                            <textarea class="form-control form-control-sm mt-1" rows="2" x-model="f.suggestion"></textarea>
                            <div class="mt-1 d-flex gap-1 justify-content-end">
                                <button type="button" class="btn btn-sm btn-success" @click="adopt(i)"><i class="bi bi-check"></i> Adopsi</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="reject(i)"><i class="bi bi-x"></i> Tolak</button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            <div x-show="analyzed && !findings.length && !error" x-cloak class="small text-success mt-2"><i class="bi bi-check-circle"></i> Tidak ada temuan signifikan dari AI.</div>
            <div x-show="error" x-cloak class="small text-danger mt-2"><i class="bi bi-exclamation-triangle"></i> <span x-text="error"></span></div>
        </div>
    </div>

    <form method="POST" action="{{ route('review.store', $document) }}">
        @csrf
        @foreach ($schema->allSections() as $section)
            @php $type = $section['type'] ?? 'text'; @endphp
            @continue(! in_array($type, $reviewTypes))
            @php
                $val = $contentMap[$section['key']] ?? [];
                $val = is_array($val) ? $val : [];
                $prior = $priorAnnotations[$section['key']] ?? collect();
            @endphp

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light fw-bold">{{ $section['label'] ?? $section['key'] }}</div>
                <div class="card-body">
                    @forelse ($val as $i => $item)
                        <div class="row g-2 mb-3 pb-2 border-bottom">
                            <div class="col-md-7">
                                <div class="small text-muted">Item {{ $i + 1 }}</div>
                                @if (is_array($item))
                                    @foreach ($item as $k => $v)
                                        @if($v && $k !== 'isi' || (is_string($v) && !str_starts_with($v,'lampiran/')))<div><span class="text-muted small">{{ $k }}:</span> {{ is_string($v) ? $v : '' }}</div>@endif
                                    @endforeach
                                @else
                                    <div>{{ $item }}</div>
                                @endif
                                @foreach ($prior->where('item_ref', (string) $i) as $a)
                                    <div class="small text-danger mt-1"><i class="bi bi-chat-left-text"></i> Catatan sebelumnya: {{ $a->comment }}</div>
                                @endforeach
                            </div>
                            <div class="col-md-5">
                                <textarea name="annotations[{{ $section['key'] }}][{{ $i }}]" data-annot="{{ $section['key'] }}-{{ $i }}" class="form-control form-control-sm" rows="2" placeholder="Catatan peninjau untuk item ini (opsional)"></textarea>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small">(Kosong)</div>
                    @endforelse
                </div>
            </div>
        @endforeach

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <label class="form-label small fw-semibold">Ringkasan / Catatan Umum (opsional)</label>
                <textarea name="summary" class="form-control mb-3" rows="2" placeholder="Ringkasan hasil tinjauan..."></textarea>
                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" name="decision" value="reject" class="btn btn-warning" onclick="return confirm('Kembalikan dokumen ke pembuat untuk revisi?')"><i class="bi bi-arrow-counterclockwise"></i> Kembalikan untuk Revisi</button>
                    <button type="submit" name="decision" value="approve" class="btn btn-success" onclick="return confirm('Loloskan dokumen ke tahap persetujuan?')"><i class="bi bi-check-lg"></i> Loloskan</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('aiReview', (url, token) => ({
            loading: false, analyzed: false, summary: '', findings: [], error: '',
            analyze() {
                this.loading = true; this.error = '';
                fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(d => {
                        this.loading = false; this.analyzed = true;
                        this.summary = d.summary || '';
                        this.findings = d.findings || [];
                        if (d.enabled === false) this.error = d.summary;
                    })
                    .catch(() => { this.loading = false; this.error = 'Gagal memanggil AI. Lanjutkan tinjauan manual.'; });
            },
            adopt(i) {
                const f = this.findings[i];
                let el = document.querySelector(`[data-annot="${f.section_key}-0"]`) || document.querySelector('[name="summary"]');
                if (el) el.value = (el.value ? el.value + '\n' : '') + f.suggestion;
                this.findings.splice(i, 1);
            },
            reject(i) { this.findings.splice(i, 1); },
            sevClass(s) { return { info: 'bg-info', minor: 'bg-secondary', major: 'bg-warning text-dark', critical: 'bg-danger' }[s] || 'bg-secondary'; },
        }));
    });
</script>
@endpush
