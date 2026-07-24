@extends('layouts.guest')
@section('title', 'Daftar')

@section('content')
    <h1 class="h4 auth-brand mb-1">Pendaftaran Akun Non-Staff</h1>
    <p class="text-muted small mb-4">Akun akan aktif setelah disetujui oleh Group Leader / Pimpinan / Admin IT.</p>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">NRP</label>
                <input type="text" name="nrp" value="{{ old('nrp') }}" class="form-control" required placeholder="Nomor Registrasi Pegawai" autocomplete="username">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Nomor HP</label>
                <input type="text" name="nomor_hp" value="{{ old('nomor_hp') }}" class="form-control" placeholder="mis. 0812xxxxxxx">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Jabatan</label>
                <input type="text" name="jabatan" value="{{ old('jabatan') }}" class="form-control" placeholder="mis. Staff ICTMD">
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
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Kata Sandi</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Ulangi Kata Sandi</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
        </div>
        <button type="submit" class="btn btn-pp w-100 py-2 mt-4"><i class="bi bi-person-plus"></i> Daftar</button>
    </form>

    <hr class="my-4">
    <p class="text-center small mb-0">
        Sudah punya akun? <a href="{{ route('login') }}" class="fw-semibold text-decoration-none" style="color:var(--pp-teal)">Masuk</a>
    </p>
@endsection
