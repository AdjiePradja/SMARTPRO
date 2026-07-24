@extends('layouts.app')
@section('title', 'Tinjau: ' . $document->displayNumber())

@php
    // Only content sections are reviewed (skip user_picker verification fields).
    // jsa_analysis WAJIB ikut: analisa JSA (langkah → bahaya → pengendalian)
    // harus bisa ditinjau sampai tingkat nested-nya.
    $reviewTypes = ['rich_list', 'reference_picker', 'repeatable_group', 'jsa_analysis'];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ route('review.index') }}" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke antrian</a>
            <h1 class="h5 fw-bold mb-0 mt-1">{{ $document->title }}</h1>
            <span class="font-monospace text-primary small">{{ $document->displayNumber() }}</span>
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
                            <label class="small text-muted mt-1 mb-0"><strong>Saran perbaikan</strong> (boleh diedit):</label>
                            <textarea class="form-control form-control-sm" rows="4" x-model="f.suggestion"></textarea>
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
                    @if ($type === 'jsa_analysis')
                        {{-- Analisa JSA ditinjau SAMPAI nested: tiap Langkah Kerja,
                             tiap Bahaya & Risiko, dan tiap Tindakan Pengendalian
                             punya kotak catatan sendiri. --}}
                        @php
                            // Checklist "Beri tanda" (#2): tiap pengendalian bisa ditandai
                            // ✓/✗ oleh peninjau. Kunci refP sama dgn anotasi & PDF.
                            $existingMarks = is_array($contentMap['jsa_checklist'] ?? null) ? $contentMap['jsa_checklist'] : [];
                            $stepRefs = [];
                            $initialMarks = [];
                            foreach ($val as $li => $step) {
                                foreach (($step['bahaya'] ?? []) as $bi => $b) {
                                    foreach (($b['pengendalian'] ?? []) as $pi => $p) {
                                        $ref = "L{$li}-B{$bi}-P{$pi}";
                                        $stepRefs[$li][] = $ref;
                                        $initialMarks[$ref] = in_array($existingMarks[$ref] ?? null, ['check', 'cross'], true) ? $existingMarks[$ref] : '';
                                    }
                                }
                            }
                        @endphp
                        <div x-data="{
                            marks: {{ \Illuminate\Support\Js::from($initialMarks) }},
                            stepRefs: {{ \Illuminate\Support\Js::from($stepRefs) }},
                            set(ref, val) { this.marks[ref] = this.marks[ref] === val ? '' : val },
                            markStep(li, val) { (this.stepRefs[li] || []).forEach(r => this.marks[r] = val) },
                            markAll(val) { Object.keys(this.marks).forEach(r => this.marks[r] = val) }
                        }">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-2 bg-body-tertiary rounded border">
                                <span class="small fw-semibold"><i class="bi bi-check2-square"></i> Beri tanda kesesuaian pengendalian:</span>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-success" @click="markAll('check')"><i class="bi bi-check-lg"></i> Centang semua</button>
                                    <button type="button" class="btn btn-outline-danger" @click="markAll('cross')"><i class="bi bi-x-lg"></i> Silang semua</button>
                                    <button type="button" class="btn btn-outline-secondary" @click="markAll('')">Kosongkan</button>
                                </div>
                                <span class="text-muted small">Hanya tanda terpilih yang tercetak di PDF.</span>
                            </div>
                        @forelse ($val as $li => $step)
                            @php $ref = "L{$li}"; @endphp
                            <div class="border rounded p-2 mb-3">
                                <div class="row g-2 mb-2 pb-2 border-bottom">
                                    <div class="col-md-7">
                                        <div class="small text-muted">Langkah Kerja {{ $li + 1 }}</div>
                                        <div class="fw-semibold">{{ $li + 1 }}. {{ $step['langkah'] ?? '' }}</div>
                                        @if (! empty($stepRefs[$li]))
                                            <div class="btn-group btn-group-sm mt-1">
                                                <button type="button" class="btn btn-outline-success" @click="markStep({{ $li }}, 'check')"><i class="bi bi-check-lg"></i> Centang langkah ini</button>
                                                <button type="button" class="btn btn-outline-danger" @click="markStep({{ $li }}, 'cross')"><i class="bi bi-x-lg"></i> Silang langkah ini</button>
                                            </div>
                                        @endif
                                        @foreach ($prior->where('item_ref', $ref) as $a)
                                            <div class="small text-danger mt-1"><i class="bi bi-chat-left-text"></i> Catatan sebelumnya: {{ $a->comment }}</div>
                                        @endforeach
                                    </div>
                                    <div class="col-md-5">
                                        <textarea name="annotations[{{ $section['key'] }}][{{ $ref }}]" data-annot="{{ $section['key'] }}-{{ $ref }}" class="form-control form-control-sm" rows="2" placeholder="Catatan untuk langkah kerja ini (opsional)"></textarea>
                                        <input type="hidden" name="annotations_ai[{{ $section['key'] }}][{{ $ref }}]" value="0" data-ai="{{ $section['key'] }}-{{ $ref }}">
                                    </div>
                                </div>

                                @foreach (($step['bahaya'] ?? []) as $bi => $b)
                                    @php $refB = "L{$li}-B{$bi}"; @endphp
                                    <div class="row g-2 mb-2 ps-3">
                                        <div class="col-md-7">
                                            <div class="small text-danger"><i class="bi bi-exclamation-triangle"></i> Bahaya &amp; Risiko {{ $li + 1 }}.{{ $bi + 1 }}</div>
                                            <div>{{ $b['risiko'] ?? '' }}</div>
                                            @foreach ($prior->where('item_ref', $refB) as $a)
                                                <div class="small text-danger mt-1"><i class="bi bi-chat-left-text"></i> Catatan sebelumnya: {{ $a->comment }}</div>
                                            @endforeach
                                        </div>
                                        <div class="col-md-5">
                                            <textarea name="annotations[{{ $section['key'] }}][{{ $refB }}]" data-annot="{{ $section['key'] }}-{{ $refB }}" class="form-control form-control-sm" rows="2" placeholder="Catatan untuk bahaya ini (opsional)"></textarea>
                                            <input type="hidden" name="annotations_ai[{{ $section['key'] }}][{{ $refB }}]" value="0" data-ai="{{ $section['key'] }}-{{ $refB }}">
                                        </div>
                                    </div>

                                    @foreach (($b['pengendalian'] ?? []) as $pi => $p)
                                        @php $refP = "L{$li}-B{$bi}-P{$pi}"; @endphp
                                        <div class="row g-2 mb-2 ps-5">
                                            <div class="col-md-7">
                                                <div class="small text-success"><i class="bi bi-shield-check"></i> Tindakan Pengendalian {{ $li + 1 }}.{{ $bi + 1 }}.{{ $pi + 1 }}</div>
                                                <div>{{ $p }}</div>
                                                {{-- Beri tanda ✓/✗ (#2): satu yang dipilih tercetak di PDF. --}}
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <span class="small text-muted">Sesuai &amp; diterapkan?</span>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" @click="set('{{ $refP }}', 'check')"
                                                            :class="marks['{{ $refP }}'] === 'check' ? 'btn btn-success' : 'btn btn-outline-success'"><i class="bi bi-check-lg"></i></button>
                                                        <button type="button" @click="set('{{ $refP }}', 'cross')"
                                                            :class="marks['{{ $refP }}'] === 'cross' ? 'btn btn-danger' : 'btn btn-outline-danger'"><i class="bi bi-x-lg"></i></button>
                                                    </div>
                                                    <input type="hidden" name="checklist[{{ $refP }}]" :value="marks['{{ $refP }}']">
                                                </div>
                                                @foreach ($prior->where('item_ref', $refP) as $a)
                                                    <div class="small text-danger mt-1"><i class="bi bi-chat-left-text"></i> Catatan sebelumnya: {{ $a->comment }}</div>
                                                @endforeach
                                            </div>
                                            <div class="col-md-5">
                                                <textarea name="annotations[{{ $section['key'] }}][{{ $refP }}]" data-annot="{{ $section['key'] }}-{{ $refP }}" class="form-control form-control-sm" rows="2" placeholder="Catatan untuk pengendalian ini (opsional)"></textarea>
                                                <input type="hidden" name="annotations_ai[{{ $section['key'] }}][{{ $refP }}]" value="0" data-ai="{{ $section['key'] }}-{{ $refP }}">
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        @empty
                            <div class="text-muted small">(Belum ada analisa bahaya)</div>
                        @endforelse
                        </div>{{-- /x-data checklist --}}
                    @else
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
                                <input type="hidden" name="annotations_ai[{{ $section['key'] }}][{{ $i }}]" value="0" data-ai="{{ $section['key'] }}-{{ $i }}">
                                <div class="small text-info mt-1 d-none" data-ai-badge="{{ $section['key'] }}-{{ $i }}"><i class="bi bi-robot"></i> Diadopsi dari saran AI</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small">(Kosong)</div>
                    @endforelse
                    @endif
                </div>
            </div>
        @endforeach

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <label class="form-label small fw-semibold">Ringkasan / Catatan Umum (opsional)</label>
                <textarea name="summary" class="form-control mb-3" rows="2" placeholder="Ringkasan hasil tinjauan..."></textarea>
                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" name="decision" value="reject" class="btn btn-warning"
                            data-confirm="Kembalikan dokumen ke pembuat untuk revisi?"
                            data-confirm-title="Kembalikan untuk Revisi?" data-confirm-icon="warning" data-confirm-ok="Ya, kembalikan"><i class="bi bi-arrow-counterclockwise"></i> Kembalikan untuk Revisi</button>
                    <button type="submit" name="decision" value="approve" class="btn btn-success"
                            data-confirm="Loloskan dokumen ke tahap persetujuan?"
                            data-confirm-title="Loloskan Dokumen?" data-confirm-ok="Ya, loloskan"><i class="bi bi-check-lg"></i> Loloskan</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Foto lampiran + komentar (v3.1 §6.2) --}}
    @if ($imageAttachments->isNotEmpty())
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-images"></i> Foto Lampiran &amp; Komentar</div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($imageAttachments as $att)
                        <div class="col-md-6">
                            <div class="border rounded p-2 h-100">
                                <img src="{{ asset('storage/'.$att->path) }}" class="img-fluid rounded mb-2" style="max-height:220px" alt="{{ $att->original_name }}">
                                <div class="small mb-2">
                                    @forelse ($att->comments as $c)
                                        <div class="border-start border-3 border-info ps-2 mb-1"><strong>{{ $c->user->name ?? '—' }}</strong>: {{ $c->comment }} <span class="text-muted">· {{ $c->created_at->diffForHumans() }}</span></div>
                                    @empty
                                        <span class="text-muted">Belum ada komentar.</span>
                                    @endforelse
                                </div>
                                <form method="POST" action="{{ route('attachments.comment', $att) }}" class="input-group input-group-sm">
                                    @csrf
                                    <input type="text" name="comment" class="form-control" placeholder="Komentari foto ini..." required maxlength="1000">
                                    <button class="btn btn-outline-primary"><i class="bi bi-send"></i></button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
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
                const f = this.findings[i], key = `${f.section_key}-0`;
                const el = document.querySelector(`[data-annot="${key}"]`);
                if (el) {
                    el.value = (el.value ? el.value + '\n' : '') + f.suggestion;
                    const m = document.querySelector(`[data-ai="${key}"]`); if (m) m.value = '1';
                    const b = document.querySelector(`[data-ai-badge="${key}"]`); if (b) b.classList.remove('d-none');
                } else {
                    const s = document.querySelector('[name="summary"]'); if (s) s.value = (s.value ? s.value + '\n' : '') + f.suggestion;
                }
                this.findings.splice(i, 1);
            },
            reject(i) { this.findings.splice(i, 1); },
            sevClass(s) { return { info: 'bg-info', minor: 'bg-secondary', major: 'bg-warning text-dark', critical: 'bg-danger' }[s] || 'bg-secondary'; },
        }));
    });
</script>
@endpush
