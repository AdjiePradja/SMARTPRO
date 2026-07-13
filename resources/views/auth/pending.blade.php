@extends('layouts.guest')
@section('title', 'Menunggu Persetujuan')

@section('content')
    <div class="text-center">
        <div class="mb-3">
            <i class="bi bi-hourglass-split text-warning" style="font-size:3rem"></i>
        </div>
        <h1 class="h4 auth-brand mb-2">Akun Menunggu Persetujuan</h1>
        <p class="text-muted">
            Halo <strong>{{ $name }}</strong>, pendaftaran Anda sudah diterima.
            Akun Anda akan aktif setelah disetujui oleh Group Leader, Pimpinan, atau Admin IT departemen Anda.
        </p>
        @if ($status === 'rejected')
            <div class="alert alert-danger small">Maaf, pendaftaran Anda ditolak. Silakan hubungi administrator departemen Anda.</div>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-left"></i> Kembali ke Login</button>
        </form>
    </div>
@endsection
