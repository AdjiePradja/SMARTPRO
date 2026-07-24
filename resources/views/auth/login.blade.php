@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
    <h3 class="font-weight-bolder text-gradient text-pp mb-1">Selamat datang</h3>
    <p class="mb-4 text-secondary">Masukkan NRP dan kata sandi untuk masuk.</p>

    @if ($errors->any())
        <div class="alert alert-danger text-white text-sm py-2">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" role="form">
        @csrf
        <label class="form-label">NRP</label>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
            <input type="text" name="nrp" value="{{ old('nrp') }}" class="form-control" required autofocus placeholder="Nomor Registrasi Pegawai" autocomplete="username">
        </div>
        <label class="form-label">Kata Sandi</label>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
        </div>
        <div class="form-check form-switch mb-3">
            <input type="checkbox" name="remember" id="remember" class="form-check-input">
            <label for="remember" class="form-check-label">Ingat saya</label>
        </div>
        <button type="submit" class="btn btn-pp w-100 mt-2 mb-0"><i class="bi bi-box-arrow-in-right"></i> Masuk</button>
    </form>

    <p class="text-center text-sm mt-4 mb-0">
        Belum punya akun?
        {{-- Warna solid (bukan text-gradient) agar tautan selalu terlihat. --}}
        <a href="{{ route('register') }}" class="fw-bold" style="color:#ea580c">Daftar sebagai Non-Staff</a>
    </p>
@endsection
