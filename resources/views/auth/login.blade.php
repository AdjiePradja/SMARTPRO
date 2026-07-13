@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
    <h1 class="h4 auth-brand mb-1">Masuk ke akun Anda</h1>
    <p class="text-muted small mb-4">Gunakan NRP dan kata sandi terdaftar.</p>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">NRP</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                <input type="text" name="nrp" value="{{ old('nrp') }}" class="form-control" required autofocus placeholder="Nomor Registrasi Pegawai" autocomplete="username">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Kata Sandi</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
        </div>
        <div class="form-check mb-4">
            <input type="checkbox" name="remember" id="remember" class="form-check-input">
            <label for="remember" class="form-check-label small">Ingat saya</label>
        </div>
        <button type="submit" class="btn btn-pp w-100 py-2"><i class="bi bi-box-arrow-in-right"></i> Masuk</button>
    </form>

    <hr class="my-4">
    <p class="text-center small mb-0">
        Belum punya akun? <a href="{{ route('register') }}" class="fw-semibold text-decoration-none" style="color:var(--pp-teal)">Daftar sebagai User Departemen</a>
    </p>
@endsection
