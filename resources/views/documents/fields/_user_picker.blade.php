{{-- user_picker: choose user(s) showing Nama + NRP + Dept (PRD v2 §2.2). --}}
@php
    $key = $section['key'];
    $multiple = $section['multiple'] ?? false;
    $required = $section['required'] ?? false;
    $options = ($candidates[$key] ?? collect());
    $selected = $value;
@endphp

<div class="mb-4">
    <label class="form-label fw-semibold">{{ $section['label'] ?? $key }}@if($required) <span class="text-danger">*</span>@endif</label>
    @isset($section['hint'])<div class="form-text mb-2">{{ $section['hint'] }}</div>@endisset

    @if ($multiple)
        <div x-data="userPicker(@js(is_array($selected) ? array_values($selected) : []))">
            <template x-for="(sel, i) in items" :key="i">
                <div class="input-group mb-2">
                    <select class="form-select" :name="`sections[{{ $key }}][]`" x-model="items[i]">
                        <option value="">— Pilih —</option>
                        @foreach ($options as $o)
                            <option value="{{ $o->id }}">{{ $o->name }} — {{ $o->nrp }} — {{ $o->department->code ?? '-' }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-danger" @click="remove(i)"><i class="bi bi-x-lg"></i></button>
                </div>
            </template>
            <button type="button" class="btn btn-sm btn-outline-primary" @click="add()"><i class="bi bi-plus-lg"></i> Tambah Pembuat</button>
        </div>
    @else
        <select class="form-select" name="sections[{{ $key }}]" @if($required) required @endif>
            <option value="">— Pilih —</option>
            @foreach ($options as $o)
                <option value="{{ $o->id }}" @selected($selected == $o->id)>{{ $o->name }} — {{ $o->nrp }} — {{ $o->department->code ?? '-' }}</option>
            @endforeach
        </select>
        @if ($options->isEmpty())
            <div class="form-text text-warning"><i class="bi bi-exclamation-triangle"></i> Belum ada kandidat untuk peran ini.</div>
        @endif
    @endif
</div>
