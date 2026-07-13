@extends('layouts.app')
@section('title', 'Buat Akun Staf')

@php
    $roleLabels = [
        'admin_it' => 'Admin IT — wewenang penuh',
        'pimpinan' => 'Pimpinan (PJO) — approver final',
        'section_head' => 'Section Head — peninjau & approver',
        'group_leader' => 'Group Leader — pembuat & peninjau',
        'staff' => 'Staff — pembuat dokumen',
    ];
@endphp

@section('content')
    <div class="mb-3">
        <a href="{{ route('users.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Kembali ke daftar user</a>
        <h1 class="h4 fw-bold text-dark mb-0 mt-1">Buat Akun Staf</h1>
        <p class="text-muted small mb-0">Akun yang dibuat admin langsung berstatus aktif.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">NRP <span class="text-muted fw-normal">(dipakai untuk login)</span></label>
                        <input type="text" name="nrp" value="{{ old('nrp') }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nomor HP</label>
                        <input type="text" name="nomor_hp" value="{{ old('nomor_hp') }}" class="form-control" placeholder="mis. 0812xxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Departemen</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">— Pilih —</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->code }} — {{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Jabatan / Peran</label>
                        <select name="role" class="form-select" required>
                            <option value="">— Pilih Jabatan —</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role') == $role)>{{ $roleLabels[$role] ?? $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label small fw-semibold">Email <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kata Sandi</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Ulangi Kata Sandi</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-pp"><i class="bi bi-check-lg"></i> Simpan Akun</button>
                    <a href="{{ route('users.index') }}" class="btn btn-light">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
