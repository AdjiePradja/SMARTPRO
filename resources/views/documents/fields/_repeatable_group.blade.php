{{-- repeatable_group: repeating block of fields (Aktivitas: sub_judul+deskripsi+PIC; Lampiran: judul+isi) --}}
@php
    $key = $section['key'];
    $prefix = $section['auto_number'] ?? '';
    $fields = $section['group_fields'] ?? $section['fields'] ?? [];
    $rows = is_array($value) ? array_values($value) : [];
    $minItems = $section['min_groups'] ?? $section['min_items'] ?? 1;
    $addLabel = $section['add_button_label'] ?? '+ Tambah';
@endphp

<div class="mb-4" x-data="repeatable(@js($rows), @js($fields), {{ $minItems }}, '{{ $prefix }}')">
    <label class="form-label fw-semibold">{{ $section['label'] ?? $key }}</label>
    @isset($section['help'])<div class="form-text mb-2">{{ $section['help'] }}</div>@endisset

    <template x-for="(row, i) in rows" :key="i">
        <div class="border rounded p-3 mb-3 bg-body-tertiary position-relative">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary font-monospace" x-text="label(i)" x-show="'{{ $prefix }}'"></span>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(i)"><i class="bi bi-trash"></i> Hapus</button>
            </div>
            @foreach ($fields as $f)
                @php $ftype = $f['type'] ?? 'text'; @endphp
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">{{ $f['label'] ?? $f['key'] }}</label>
                    @if ($ftype === 'textarea')
                        <textarea class="form-control" rows="3" :name="`sections[{{ $key }}][${i}][{{ $f['key'] }}]`" x-model="row['{{ $f['key'] }}']" placeholder="{{ $f['placeholder'] ?? '' }}"></textarea>
                    @elseif ($ftype === 'image')
                        {{-- Gambar diunggah LANGSUNG saat dipilih (AJAX) → path disimpan di
                             hidden input. Preview cukup memuat ulang iframe (tanpa reload
                             halaman) & foto tak hilang saat pindah langkah. --}}
                        <div x-data="{
                            fkey: '{{ $f['key'] }}', uploading: false, err: '',
                            get hasImg() { return this.row[this.fkey] && String(this.row[this.fkey]).startsWith('lampiran/'); },
                            async up(e) {
                                const file = e.target.files[0]; if (!file) return;
                                this.err = ''; this.uploading = true;
                                const fd = new FormData(); fd.append('image', file); fd.append('section', '{{ $key }}');
                                try {
                                    const r = await fetch('{{ route('documents.uploadAttachment', $document) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
                                    const j = await r.json();
                                    if (r.ok && j.path) { this.row[this.fkey] = j.path; } else { this.err = j.message || 'Gagal mengunggah gambar.'; }
                                } catch (_) { this.err = 'Gagal mengunggah gambar.'; }
                                this.uploading = false; e.target.value = '';
                            }
                        }">
                            <input type="hidden" :name="`sections[{{ $key }}][${i}][{{ $f['key'] }}]`" :value="row[fkey]">
                            <template x-if="hasImg">
                                <div class="mb-2">
                                    <img :src="'/storage/' + row[fkey]" class="img-thumbnail" style="max-height:160px">
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" @click="row[fkey]=''"><i class="bi bi-x"></i> Hapus gambar</button>
                                </div>
                            </template>
                            <input type="file" accept="{{ $f['image_accept'] ?? 'image/jpeg,image/png' }}" class="form-control" @change="up($event)" :disabled="uploading">
                            <div class="form-text" x-show="!uploading">Format JPG/PNG, maks {{ $f['image_max_mb'] ?? 2 }}MB. Foto akan muncul di preview &amp; PDF.</div>
                            <div class="form-text text-primary" x-show="uploading" x-cloak><span class="spinner-border spinner-border-sm"></span> Mengunggah…</div>
                            <div class="text-danger small mt-1" x-show="err" x-text="err" x-cloak></div>
                        </div>
                    @else
                        <input type="text" class="form-control" :name="`sections[{{ $key }}][${i}][{{ $f['key'] }}]`" x-model="row['{{ $f['key'] }}']" placeholder="{{ $f['placeholder'] ?? '' }}">
                    @endif
                </div>
            @endforeach
        </div>
    </template>

    <button type="button" class="btn btn-sm btn-outline-primary" @click="add()"><i class="bi bi-plus-lg"></i> {{ $addLabel }}</button>
</div>
