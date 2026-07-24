{{-- jsa_analysis: nested 3 tingkat — Langkah Kerja → Bahaya & Risiko → Tindakan Pengendalian --}}
@php $key = $section['key']; $rows = is_array($value) ? array_values($value) : []; @endphp

<div class="mb-4" x-data="jsaAnalysis(@js($rows))">
    <label class="form-label fw-semibold">{{ $section['label'] ?? $key }}</label>
    @isset($section['help'])<div class="alert alert-info py-2 small mb-3"><i class="bi bi-info-circle"></i> {{ $section['help'] }}</div>@endisset

    <template x-for="(step, li) in steps" :key="li">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Langkah Kerja #<span x-text="li + 1"></span></span>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeStep(li)"><i class="bi bi-trash"></i> Hapus Langkah</button>
            </div>
            <div class="card-body">
                <label class="form-label small fw-semibold">Uraian Langkah Pekerjaan</label>
                <textarea class="form-control mb-3" rows="2" :name="`sections[{{ $key }}][${li}][langkah]`" x-model="step.langkah" placeholder="mis. Persiapan alat dan pengecekan area kerja"></textarea>

                <template x-for="(bahaya, bi) in step.bahaya" :key="bi">
                    <div class="border rounded p-3 mb-2 bg-body-tertiary">
                        <label class="form-label small fw-semibold text-danger mb-1"><i class="bi bi-exclamation-triangle"></i> Bahaya &amp; Risiko</label>
                        <div class="d-flex gap-2 mb-2">
                            <input type="text" class="form-control flex-grow-1" :name="`sections[{{ $key }}][${li}][bahaya][${bi}][risiko]`" x-model="bahaya.risiko" placeholder="Potensi bahaya / risiko yang mungkin timbul">
                            <button type="button" class="btn btn-outline-danger btn-field" @click="removeBahaya(li, bi)" title="Hapus bahaya ini"><i class="bi bi-x-lg"></i></button>
                        </div>

                        <label class="form-label small fw-semibold text-success mb-1"><i class="bi bi-shield-check"></i> Tindakan Pengendalian</label>
                        <template x-for="(kendali, pi) in bahaya.pengendalian" :key="pi">
                            <div class="d-flex gap-2 mb-1">
                                <input type="text" class="form-control form-control-sm flex-grow-1" :name="`sections[{{ $key }}][${li}][bahaya][${bi}][pengendalian][${pi}]`" x-model="bahaya.pengendalian[pi]" placeholder="Langkah pengendalian bahaya">
                                <button type="button" class="btn btn-sm btn-outline-success btn-field" @click="addKendali(li, bi)" title="Tambah pengendalian"><i class="bi bi-plus-lg"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-field" @click="removeKendali(li, bi, pi)" title="Hapus" x-show="bahaya.pengendalian.length > 1"><i class="bi bi-dash-lg"></i></button>
                            </div>
                        </template>
                    </div>
                </template>

                <button type="button" class="btn btn-sm btn-outline-primary mt-1" @click="addBahaya(li)"><i class="bi bi-plus-lg"></i> Tambah Bahaya</button>
            </div>
        </div>
    </template>

    <button type="button" class="btn btn-pp w-100" @click="addStep()"><i class="bi bi-plus-lg"></i> Tambah Langkah Kerja Baru</button>
</div>
