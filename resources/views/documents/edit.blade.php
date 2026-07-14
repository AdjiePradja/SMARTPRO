@extends('layouts.app')
@section('title', 'Pengisian Dokumen')

@php
    $partials = [
        'rich_list' => 'documents.fields._rich_list',
        'reference_picker' => 'documents.fields._rich_list',
        'repeatable_group' => 'documents.fields._repeatable_group',
        'user_picker' => 'documents.fields._user_picker',
        'text' => 'documents.fields._text',
        'textarea' => 'documents.fields._text',
    ];
    $isLast = $document->current_step >= $schema->stepCount();
@endphp

@section('content')
    {{-- Kop / header info (Langkah 1 header, PRD v2 §4.1) --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <div class="font-monospace text-primary fw-bold">{{ $document->doc_number }}</div>
                    <h1 class="h5 fw-bold mb-1">{{ $document->title }}</h1>
                    <span class="badge bg-secondary text-light border">{{ $document->type->code }}</span>
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

    {{-- 2-step progress --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-3">
            @foreach ($schema->steps() as $step)
                @php $n = $step['step']; $isCurrent = $n == $document->current_step; @endphp
                <div class="d-flex align-items-center">
                    <span class="badge rounded-pill {{ $isCurrent ? 'bg-primary' : ($n < $document->current_step ? 'bg-success' : 'bg-light text-muted border') }}"
                          style="width:1.9rem;height:1.9rem;line-height:1.4rem">{{ $n }}</span>
                    <span class="ms-2 small {{ $isCurrent ? 'fw-bold text-primary' : 'text-muted' }}">Langkah {{ $n }}: {{ $schema->stepTitle($n) }}</span>
                    @if (! $loop->last)<i class="bi bi-chevron-right text-muted mx-2"></i>@endif
                </div>
            @endforeach
        </div>
    </div>

    @unless ($editable)
        <div class="alert alert-warning"><i class="bi bi-lock"></i> Dokumen berstatus <strong>{{ $document->statusLabel() }}</strong> — form dalam mode baca.</div>
    @endunless

    @php $annotations = $annotations ?? collect(); @endphp
    @if ($annotations->isNotEmpty())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2"><i class="bi bi-chat-left-text"></i> Catatan Peninjau — perbaiki item berikut:</div>
            <ul class="mb-0 small">
                @foreach ($annotations as $sectionKey => $list)
                    @foreach ($list as $a)
                        <li><span class="badge bg-danger-subtle text-danger-emphasis">{{ $sectionKey }}</span> {{ $a->comment }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('documents.saveStep', $document) }}" enctype="multipart/form-data"
          x-data="wizard('{{ route('documents.autosave', $document) }}', '{{ route('documents.preview', $document) }}', '{{ csrf_token() }}')" @submit="submitting = true">
        @csrf
        <input type="hidden" name="step" value="{{ $document->current_step }}">

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Langkah {{ $document->current_step }} — {{ $schema->stepTitle($document->current_step) }}</span>
                <span class="small text-muted" x-show="savedAt" x-cloak><i class="bi bi-cloud-check"></i> Tersimpan otomatis <span x-text="savedAt"></span></span>
            </div>
            <div class="card-body" @input.debounce.1200ms="autosave()">
                @foreach ($schema->sectionsForStep($document->current_step) as $section)
                    @php
                        $partial = $partials[$section['type']] ?? 'documents.fields._text';
                        $val = ($section['type'] === 'user_picker') ? ($userValues[$section['key']] ?? null) : ($contentMap[$section['key']] ?? null);
                    @endphp
                    @include($partial, ['section' => $section, 'value' => $val, 'document' => $document, 'candidates' => $candidates ?? []])
                @endforeach
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <div>
                    @if ($document->current_step > 1)
                        <button type="submit" name="action" value="back" class="btn btn-light"><i class="bi bi-arrow-left"></i> Kembali</button>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-info" @click="preview()" data-bs-toggle="modal" data-bs-target="#previewModal"><i class="bi bi-eye"></i> Preview</button>
                    @if ($editable)
                        @unless ($isLast)
                            <button type="submit" name="action" value="next" class="btn pp-border">Langkah Berikutnya <i class="bi bi-arrow-right"></i></button>
                        @else
                            <button type="submit" name="action" value="save" class="btn btn-outline-primary"><i class="bi bi-save"></i> Simpan</button>
                            <button type="submit" name="action" value="submit" class="btn btn-success"><i class="bi bi-send"></i> Kirim</button>
                        @endunless
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Preview 1:1 modal (full-screen), renders the same print template --}}
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title h6 mb-0"><i class="bi bi-eye"></i> Preview 1:1 — {{ $document->doc_number }}</h5>&nbsp;
                    <div class="d-flex justify-content-evenly gap-2">
                        <a href="{{ route('documents.pdf', $document) }}" target="_blank" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> Buka PDF</a>
                        <button type="button" class="btn-close" style="margin-top: 0.2px;" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body p-0" style="background:#525659">
                    <iframe id="previewFrame" title="Preview" style="width:100%;height:100%;border:0;background:#fff"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function emptyRow(fields) {
        const o = {};
        (fields || []).forEach(f => o[f.key] = '');
        return o;
    }
    document.addEventListener('alpine:init', () => {
        Alpine.data('richList', (initial = [], prefix = '') => ({
            items: initial.length ? initial : [''],
            prefix,
            add() { this.items.push(''); },
            remove(i) { this.items.splice(i, 1); if (!this.items.length) this.items.push(''); },
            label(i) { return this.prefix + (i + 1); },
        }));
        Alpine.data('repeatable', (initial = [], fields = [], min = 1, prefix = '') => ({
            rows: initial.length ? initial : (min > 0 ? [emptyRow(fields)] : []),
            fields, prefix,
            add() { this.rows.push(emptyRow(this.fields)); },
            remove(i) { this.rows.splice(i, 1); },
            label(i) { return this.prefix + (i + 1); },
        }));
        Alpine.data('userPicker', (initial = []) => ({
            items: initial.length ? initial : [''],
            add() { this.items.push(''); },
            remove(i) { this.items.splice(i, 1); if (!this.items.length) this.items.push(''); },
        }));
        Alpine.data('wizard', (autosaveUrl, previewUrl, token) => ({
            savedAt: '', submitting: false, previewUrl,
            _post() {
                const data = new FormData(this.$root);
                data.append('autosave', '1');
                return fetch(autosaveUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }, body: data })
                    .then(r => r.ok ? r.json() : null);
            },
            autosave() {
                if (this.submitting) return;
                this._post().then(j => { if (j && j.saved_at) this.savedAt = j.saved_at; }).catch(() => {});
            },
            preview() {
                const load = () => { const f = document.getElementById('previewFrame'); if (f) f.src = this.previewUrl + '?t=' + Date.now(); };
                this._post().then(load, load);
            },
        }));
    });
</script>
@endpush
