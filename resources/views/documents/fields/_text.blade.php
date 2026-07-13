{{-- text / textarea section (single value) --}}
@php $key = $section['key']; $type = $section['type'] ?? 'text'; @endphp

<div class="mb-4">
    <label class="form-label fw-semibold">{{ $section['label'] ?? $key }}</label>
    @isset($section['help'])<div class="form-text mb-2">{{ $section['help'] }}</div>@endisset
    @if ($type === 'textarea')
        <textarea class="form-control" rows="4" name="sections[{{ $key }}]" placeholder="{{ $section['placeholder'] ?? '' }}">{{ is_string($value) ? $value : '' }}</textarea>
    @else
        <input type="text" class="form-control" name="sections[{{ $key }}]" value="{{ is_string($value) ? $value : '' }}" placeholder="{{ $section['placeholder'] ?? '' }}">
    @endif
</div>
