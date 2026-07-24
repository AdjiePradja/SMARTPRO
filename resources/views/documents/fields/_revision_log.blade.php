{{-- Langkah "Log Revisi" (hanya draft revisi Tipe B) — mengisi lembar CATATAN
     REVISI (docs/Lembar Revisi.docx). Referensi form: docs/lembar revisi/*.png.
     Baris salinan revisi lama membawa no_rev-nya; baris baru diberi no_revisi
     dokumen ini di server (persistRevisionLog). --}}
@php
    $rows = collect(is_array($value ?? null) ? $value : [])->values()->all();
@endphp

<div x-data="revisionLog(@js($rows))">
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Edisi</label>
            <input type="number" min="1" name="edisi" value="{{ (int) ($document->edisi ?: 1) }}" class="form-control">
            <div class="form-text">Otomatis (roll-over) — boleh diubah manual.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Revisi</label>
            <input type="number" min="0" name="no_revisi" value="{{ (int) $document->no_revisi }}" class="form-control">
            <div class="form-text">Otomatis — boleh diubah manual.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Judul Dokumen</label>
            <input type="text" class="form-control" value="{{ $document->title }}" readonly disabled>
        </div>
    </div>

    <label class="form-label fw-semibold">Catatan Perubahan (Revisi)</label>
    <div class="form-text mb-2">Satu baris = satu catatan pada lembar CATATAN REVISI (kolom: No. Rev / Tanggal Rev / Hal. / Catatan — nomor urut otomatis). Baris revisi terdahulu ikut terbawa & tercetak.</div>

    <template x-for="(row, i) in rows" :key="i">
        <div class="border rounded p-3 mb-2 bg-body-tertiary">
            <div class="row g-2 align-items-start">
                <div class="col-md-2">
                    <label class="form-label small mb-1">No. Rev</label>
                    <input type="number" min="0" class="form-control" :name="`sections[catatan_revisi][${i}][no_rev]`" x-model="row.no_rev" placeholder="{{ (int) $document->no_revisi }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Tanggal Rev</label>
                    <input type="date" class="form-control" :name="`sections[catatan_revisi][${i}][tanggal]`" x-model="row.tanggal">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Hal.</label>
                    <input type="text" class="form-control" placeholder="mis. 1-4" :name="`sections[catatan_revisi][${i}][halaman]`" x-model="row.halaman">
                </div>
                <div class="col-md-5">
                    <label class="form-label small mb-1">Catatan Revisi</label>
                    <textarea class="form-control" rows="2" placeholder="mis. Perubahan detail aktivitas..." :name="`sections[catatan_revisi][${i}][catatan]`" x-model="row.catatan"></textarea>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger mt-2" @click="remove(i)"><i class="bi bi-trash"></i> Hapus Poin Catatan</button>
        </div>
    </template>

    <button type="button" class="btn btn-sm btn-outline-primary w-100" @click="add()"><i class="bi bi-plus-lg"></i> Tambah Catatan Per Halaman</button>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('revisionLog', (initial = []) => ({
            rows: initial.length ? initial : [{ no_rev: null, tanggal: '', halaman: '', catatan: '' }],
            add() { this.rows.push({ no_rev: null, tanggal: '', halaman: '', catatan: '' }); },
            remove(i) { this.rows.splice(i, 1); if (!this.rows.length) this.add(); },
        }));
    });
</script>
@endpush
