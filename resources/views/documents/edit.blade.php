@extends('layouts.app')
@section('title', 'Pengisian Dokumen')

@php
    $partials = [
        'rich_list' => 'documents.fields._rich_list',
        'reference_picker' => 'documents.fields._rich_list',
        'repeatable_group' => 'documents.fields._repeatable_group',
        'jsa_analysis' => 'documents.fields._jsa_analysis',
        'user_picker' => 'documents.fields._user_picker',
        'text' => 'documents.fields._text',
        'textarea' => 'documents.fields._text',
        'date' => 'documents.fields._text',   // widget tanggal (bukan diketik)
    ];
    // Draft revisi Tipe B punya langkah ekstra "Log Revisi" di akhir (form lembar
    // CATATAN REVISI); Simpan/Kirim pindah ke sana.
    $totalSteps = $totalSteps ?? $schema->stepCount();
    $isRevLogStep = $document->isRevisionDraft() && $currentStep > $schema->stepCount();
    $isLast = $currentStep >= $totalSteps;
@endphp

@section('content')
    {{-- Kop / header info --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <div class="font-monospace text-primary fw-bold">
                        {{ $document->displayNumber() }}
                        @unless ($document->hasFinalNumber())
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="Nomor final dikunci setelah disetujui">sementara</span>
                        @endunless
                    </div>
                    <h1 class="h5 fw-bold mb-1">{{ $document->title }}</h1>
                    <span class="badge bg-light text-dark border">{{ $document->type->code }}</span>
                    <span class="badge bg-secondary">{{ $document->department->code }}</span>
                    <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $document->statusLabel() }}</span>
                </div>
                <div class="text-end small text-muted">
                    <div>Pembuat: {{ $document->creator->name ?? '—' }}</div>
                    <div>No. Revisi: {{ $document->no_revisi }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress langkah (+ langkah "Log Revisi" khusus draft revisi) --}}
    @php
        $stepTitles = collect($schema->steps())->mapWithKeys(fn ($s) => [$s['step'] => $s['title'] ?? '']);
        if ($document->isRevisionDraft()) $stepTitles->put($schema->stepCount() + 1, 'Log Revisi (Catatan Perubahan)');
    @endphp
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-3">
            @foreach ($stepTitles as $n => $title)
                @php $isCurrent = $n == $currentStep; @endphp
                <div class="d-flex align-items-center">
                    <span class="badge rounded-pill {{ $isCurrent ? 'bg-primary' : ($n < $currentStep ? 'bg-success' : 'bg-light text-muted border') }}"
                          style="width:1.9rem;height:1.9rem;line-height:1.4rem">{{ $n }}</span>
                    <span class="ms-2 small {{ $isCurrent ? 'fw-bold text-primary' : 'text-muted' }}">Langkah {{ $n }}: {{ $title }}</span>
                    @if (! $loop->last)<i class="bi bi-chevron-right text-muted mx-2"></i>@endif
                </div>
            @endforeach
        </div>
    </div>

    @unless ($editable)
        <div class="alert alert-warning"><i class="bi bi-lock"></i> Dokumen berstatus <strong>{{ $document->statusLabel() }}</strong> — mode baca.</div>
    @endunless

    @php $annotations = $annotations ?? collect(); $reviewSummary = $reviewSummary ?? null; @endphp
    @if ($annotations->isNotEmpty() || $reviewSummary)
        <div class="alert alert-danger">
            @if ($reviewSummary)
                <div class="mb-2 pb-2 border-bottom border-danger-subtle"><i class="bi bi-card-text"></i> <strong>Rangkuman Peninjau:</strong> {{ $reviewSummary }}</div>
            @endif
            @if ($annotations->isNotEmpty())
            <div class="fw-semibold mb-2"><i class="bi bi-chat-left-text"></i> Catatan Peninjau — perbaiki item berikut:</div>
            <ul class="mb-0 small">
                @foreach ($annotations as $sectionKey => $list)
                    @foreach ($list as $a)<li><span class="badge bg-danger-subtle text-danger-emphasis">{{ $sectionKey }}</span> {{ $a->comment }}</li>@endforeach
                @endforeach
            </ul>
            @endif
        </div>
    @endif

    {{-- Form (kiri) + Preview panel (kanan) — PRD v3.1 §6 --}}
    <div class="row g-3">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('documents.saveStep', $document) }}" enctype="multipart/form-data"
                  x-data="wizard('{{ route('documents.autosave', $document) }}', '{{ route('documents.preview', $document) }}', '{{ csrf_token() }}')" @submit="onSubmit($event)">
                @csrf
                <input type="hidden" name="step" value="{{ $currentStep }}">

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Langkah {{ $currentStep }} — {{ $isRevLogStep ? 'Log Revisi (Catatan Perubahan)' : $schema->stepTitle($currentStep) }}</span>
                        <span class="small text-muted" x-show="savedAt" x-cloak><i class="bi bi-cloud-check"></i> Tersimpan otomatis <span x-text="savedAt"></span></span>
                    </div>
                    <div class="card-body" @input.debounce.1200ms="autosave()">
                        @if ($isRevLogStep)
                            @include('documents.fields._revision_log', ['document' => $document, 'value' => $contentMap['catatan_revisi'] ?? []])
                        @else
                            @foreach ($schema->sectionsForStep($currentStep) as $section)
                                @php
                                    $partial = $partials[$section['type']] ?? 'documents.fields._text';
                                    $val = in_array($section['type'], ['user_picker']) ? ($userValues[$section['key']] ?? null) : ($contentMap[$section['key']] ?? null);
                                @endphp
                                @include($partial, ['section' => $section, 'value' => $val, 'document' => $document, 'candidates' => $candidates ?? [], 'userValues' => $userValues ?? []])
                            @endforeach
                        @endif
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between">
                        <div>
                            @if ($currentStep > 1)
                                @if ($editable)
                                    <button type="submit" name="action" value="back" class="btn btn-light"><i class="bi bi-arrow-left"></i> Kembali</button>
                                @else
                                    <a href="{{ route('documents.edit', ['document' => $document, 'view_step' => $currentStep - 1]) }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Kembali</a>
                                @endif
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            {{-- Preview = AJAX: hanya panel/iframe yang dimuat ulang, halaman
                                 form TIDAK ikut ter-refresh. Foto lampiran sudah tersimpan
                                 saat dipilih (upload langsung), jadi ikut tampil di preview. --}}
                            <button type="button" class="btn btn-outline-secondary" @click="preview()" :disabled="previewing">
                                <span x-show="previewing" class="spinner-border spinner-border-sm" x-cloak></span>
                                <i class="bi bi-eye" x-show="!previewing"></i> Preview
                            </button>
                            @if ($editable)
                                @unless ($isLast)
                                    <button type="submit" name="action" value="next" class="btn btn-pp">Langkah Berikutnya <i class="bi bi-arrow-right"></i></button>
                                @else
                                    <button type="submit" name="action" value="save" class="btn btn-outline-primary"><i class="bi bi-save"></i> Simpan</button>
                                    <button type="submit" name="action" value="submit" class="btn btn-success" @click.prevent="confirmKirim()"><i class="bi bi-send"></i> Kirim</button>
                                @endunless
                            @elseif (! $isLast)
                                <a href="{{ route('documents.edit', ['document' => $document, 'view_step' => $currentStep + 1]) }}" class="btn btn-pp">Langkah Berikutnya <i class="bi bi-arrow-right"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Preview panel kanan --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top:1rem">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold small"><i class="bi bi-eye"></i> Preview 1:1</span>
                    <a href="{{ route('documents.pdf', $document) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                </div>
                <div class="card-body p-0" style="background:#525659;height:70vh">
                    <div id="previewPlaceholder" class="d-flex flex-column align-items-center justify-content-center h-100 text-white-50 small">
                        <i class="bi bi-file-earmark-text fs-1 mb-2"></i>
                        Klik <strong class="mx-1">Preview</strong> untuk menampilkan dokumen di sini.
                    </div>
                    <iframe id="previewFrame" title="Preview" class="d-none" style="width:100%;height:100%;border:0;background:#fff"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@push('scripts')
<script>
    function emptyRow(fields) { const o = {}; (fields || []).forEach(f => o[f.key] = ''); return o; }
    document.addEventListener('alpine:init', () => {
        Alpine.data('richList', (initial = [], prefix = '') => ({
            items: initial.length ? initial : [''], prefix,
            add() { this.items.push(''); },
            remove(i) { this.items.splice(i, 1); if (!this.items.length) this.items.push(''); },
            label(i) { return this.prefix + (i + 1); },
        }));
        Alpine.data('repeatable', (initial = [], fields = [], min = 1, prefix = '') => ({
            rows: initial.length ? initial : (min > 0 ? [emptyRow(fields)] : []), fields, prefix,
            add() { this.rows.push(emptyRow(this.fields)); },
            remove(i) { this.rows.splice(i, 1); },
            label(i) { return this.prefix + (i + 1); },
        }));
        Alpine.data('userPicker', (initial = []) => ({
            items: initial.length ? initial : [''],
            add() { this.items.push(''); },
            remove(i) { this.items.splice(i, 1); if (!this.items.length) this.items.push(''); },
        }));
        // JSA: analisa bahaya nested (Langkah Kerja → Bahaya → Pengendalian)
        const jsaBahaya = () => ({ risiko: '', pengendalian: [''] });
        const jsaStep = () => ({ langkah: '', bahaya: [jsaBahaya()] });
        Alpine.data('jsaAnalysis', (initial = []) => ({
            steps: (Array.isArray(initial) && initial.length) ? initial.map(s => ({
                langkah: s.langkah || '',
                bahaya: (Array.isArray(s.bahaya) && s.bahaya.length ? s.bahaya : [jsaBahaya()]).map(b => ({
                    risiko: b.risiko || '',
                    pengendalian: (Array.isArray(b.pengendalian) && b.pengendalian.length) ? b.pengendalian : [''],
                })),
            })) : [jsaStep()],
            addStep() { this.steps.push(jsaStep()); },
            removeStep(li) { this.steps.splice(li, 1); if (!this.steps.length) this.steps.push(jsaStep()); },
            addBahaya(li) { this.steps[li].bahaya.push(jsaBahaya()); },
            removeBahaya(li, bi) { this.steps[li].bahaya.splice(bi, 1); if (!this.steps[li].bahaya.length) this.steps[li].bahaya.push(jsaBahaya()); },
            addKendali(li, bi) { this.steps[li].bahaya[bi].pengendalian.push(''); },
            removeKendali(li, bi, pi) { const p = this.steps[li].bahaya[bi].pengendalian; p.splice(pi, 1); if (!p.length) p.push(''); },
        }));
        Alpine.data('wizard', (autosaveUrl, previewUrl, token) => ({
            savedAt: '', submitting: false, previewing: false, previewUrl,
            // Preview selalu dimuat saat halaman terbuka → panel tak pernah kosong
            // antar langkah (revisi #2).
            init() { this.loadPreview(); },
            loadPreview() {
                const f = document.getElementById('previewFrame'), ph = document.getElementById('previewPlaceholder');
                if (!f) { this.previewing = false; return; }
                f.onload = () => { this.previewing = false; };
                f.src = this.previewUrl + '?t=' + Date.now();
                f.classList.remove('d-none'); if (ph) ph.classList.add('d-none');
            },
            _post() {
                const data = new FormData(this.$root); data.append('autosave', '1');
                return fetch(autosaveUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }, body: data }).then(r => r.ok ? r.json() : null);
            },
            autosave() { if (this.submitting) return; this._post().then(j => { if (j && j.saved_at) this.savedAt = j.saved_at; }).catch(() => {}); },
            // Simpan teks dulu (AJAX) lalu muat ulang HANYA iframe preview — form tak reload.
            preview() { this.previewing = true; this._post().finally(() => this.loadPreview()); },
            // Validasi hanya saat MAJU (next) atau kirim — Simpan/Kembali boleh parsial.
            onSubmit(e) {
                if (e.submitter && e.submitter.value === 'next' && !ppValidateRequired(this.$root)) {
                    e.preventDefault();
                    return;
                }
                this.submitting = true;
            },
            confirmKirim() {
                // Validasi dulu: semua field wajib harus terisi (arahkan ke yg kosong).
                if (!ppValidateRequired(this.$root)) return;
                Swal.fire({
                    title: 'Kirim Dokumen?',
                    text: 'Kirim dokumen untuk ditinjau? Dokumen tidak bisa diedit setelah dikirim (masih bisa Ditarik selama belum ditinjau).',
                    icon: 'warning', showCancelButton: true,
                    confirmButtonText: 'Ya, kirim', cancelButtonText: 'Batal',
                    confirmButtonColor: '#ea580c', cancelButtonColor: '#6c757d',
                }).then((r) => {
                    if (!r.isConfirmed) return;
                    this.submitting = true;
                    this.$root.querySelector('input[name=action]')?.remove();
                    const h = document.createElement('input'); h.type = 'hidden'; h.name = 'action'; h.value = 'submit'; this.$root.appendChild(h);
                    this.$root.submit();
                });
            },
        }));
    });
</script>
@endpush
