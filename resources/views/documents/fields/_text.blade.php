{{-- text / textarea section (single value) --}}
@php
    $key = $section['key'];
    $type = $section['type'] ?? 'text';
    // Nilai: isi tersimpan; jika kosong pakai 'default' schema (mis. metadata form JSA).
    $val = (is_string($value) && $value !== '') ? $value : ($section['default'] ?? '');
@endphp

<div class="mb-4">
    <label class="form-label fw-semibold">{{ $section['label'] ?? $key }}</label>
    @isset($section['help'])<div class="form-text mb-2">{{ $section['help'] }}</div>@endisset
    @if ($type === 'textarea')
        <textarea class="form-control" rows="4" name="sections[{{ $key }}]" placeholder="{{ $section['placeholder'] ?? '' }}">{{ $val }}</textarea>
    @elseif ($type === 'date')
        {{-- Kolom tanggal WAJIB memakai widget tanggal (bukan diketik). --}}
        <input type="date" class="form-control" name="sections[{{ $key }}]" value="{{ $val }}">
    @else
        <input type="text" class="form-control" name="sections[{{ $key }}]" value="{{ $val }}" placeholder="{{ $section['placeholder'] ?? '' }}">
    @endif
</div>
