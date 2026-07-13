@extends('layouts.app')
@section('title', 'Jenis Dokumen Belum Tersedia')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-cone-striped text-warning" style="font-size:3rem"></i>
            <h1 class="h4 fw-bold text-dark mt-3">Dokumen {{ $type->code }} Belum Tersedia</h1>
            <p class="text-muted mb-1">{{ $type->name }}</p>
            <p class="text-muted col-md-8 mx-auto">
                Schema untuk jenis dokumen ini sedang menunggu contoh dokumen dari tim.
                Saat ini baru <strong>SOP</strong> yang siap digunakan. Jenis lain akan aktif
                setelah format & contohnya tersedia.
            </p>
            <a href="{{ route('documents.create', ['type' => 'SOP']) }}" class="btn btn-pp mt-2"><i class="bi bi-file-earmark-plus"></i> Buat Dokumen SOP</a>
            <a href="{{ route('documents.index') }}" class="btn btn-light mt-2">Kembali</a>
        </div>
    </div>
@endsection
