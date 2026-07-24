{{-- rich_list / reference_picker: an editable, auto-numbered list of text items --}}
@php
    $key = $section['key'];
    $prefix = $section['auto_number'] ?? '';
    $items = is_array($value) ? array_values($value) : [];
    $suggestions = $section['suggestions'] ?? null;
@endphp

<div class="mb-4" x-data="richList(@js($items), '{{ $prefix }}')">
    <label class="form-label fw-semibold">{{ $section['label'] ?? $key }}</label>
    @isset($section['help'])<div class="form-text mb-2">{{ $section['help'] }}</div>@endisset

    @if ($suggestions)
        <datalist id="dl-{{ $key }}">
            @foreach ($suggestions as $s)<option value="{{ $s }}">@endforeach
        </datalist>
    @endif

    {{-- Baris flex gap-2 (bukan input-group rapat): field & tombol ikon sejajar
         tinggi (btn-field align-self stretch) dan BERJARAK (v3 rev tampilan #4). --}}
    <template x-for="(item, i) in items" :key="i">
        <div class="d-flex gap-2 mb-2">
            <span class="input-group-text font-monospace btn-field" x-text="label(i)" x-show="'{{ $prefix }}'"></span>
            <input type="text" class="form-control flex-grow-1" name="sections[{{ $key }}][]" x-model="items[i]"
                   @if($suggestions) list="dl-{{ $key }}" @endif placeholder="{{ $suggestions ? 'Masukkan referensi...' : 'Masukkan poin...' }}">
            <button type="button" class="btn btn-outline-danger btn-field" @click="remove(i)" title="Hapus"><i class="bi bi-x-lg"></i></button>
        </div>
    </template>

    <button type="button" class="btn btn-sm btn-outline-primary" @click="add()"><i class="bi bi-plus-lg"></i> Tambah Poin</button>
</div>
