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
                    @elseif ($ftype === 'text_or_image')
                        {{-- Teks ATAU gambar. ONE hidden input carries the persisted value
                             (row[field]); the textarea is UI-only to avoid duplicate names. --}}
                        <div x-data="{
                                fkey: '{{ $f['key'] }}',
                                get isImage() { return this.row[this.fkey] && String(this.row[this.fkey]).startsWith('lampiran/'); },
                                setMode(m) {
                                    if (m === 'image' && !this.isImage) this.row[this.fkey] = '';
                                    if (m === 'text' && this.isImage) this.row[this.fkey] = '';
                                    this.mode = m;
                                }
                             }" x-init="mode = isImage ? 'image' : 'text'">
                            <input type="hidden" :name="`sections[{{ $key }}][${i}][{{ $f['key'] }}]`" :value="row[fkey]">
                            <div class="btn-group btn-group-sm mb-2" role="group">
                                <button type="button" class="btn" :class="mode==='text' ? 'btn-primary' : 'btn-outline-secondary'" @click="setMode('text')"><i class="bi bi-fonts"></i> Teks</button>
                                <button type="button" class="btn" :class="mode==='image' ? 'btn-primary' : 'btn-outline-secondary'" @click="setMode('image')"><i class="bi bi-image"></i> Gambar</button>
                            </div>
                            <div x-show="mode==='text'">
                                <textarea class="form-control" rows="2" x-model="row[fkey]" placeholder="{{ $f['text_placeholder'] ?? '' }}"></textarea>
                            </div>
                            <div x-show="mode==='image'" x-cloak>
                                <template x-if="isImage">
                                    <div class="mb-2">
                                        <img :src="'/storage/' + row[fkey]" class="img-thumbnail" style="max-height:150px">
                                        <button type="button" class="btn btn-sm btn-outline-danger ms-2" @click="row[fkey]=''"><i class="bi bi-x"></i> Hapus gambar</button>
                                    </div>
                                </template>
                                <input type="file" accept="{{ $f['image_accept'] ?? 'image/jpeg,image/png' }}" class="form-control" :name="`files[{{ $key }}][${i}][{{ $f['key'] }}]`">
                                <div class="form-text">Format JPG/PNG, maks {{ $f['image_max_mb'] ?? 2 }}MB.</div>
                            </div>
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
