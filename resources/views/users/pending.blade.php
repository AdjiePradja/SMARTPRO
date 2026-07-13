@extends('layouts.app')
@section('title', 'Persetujuan Akun')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">Persetujuan Akun</h1>
            <p class="text-muted small mb-0">Setujui atau tolak pendaftaran User Departemen.</p>
        </div>
        <span class="badge bg-warning text-dark fs-6">{{ $pendingUsers->count() }} menunggu</span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if ($pendingUsers->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-check2-circle fs-1 d-block mb-2 text-success"></i>
                    Tidak ada akun yang menunggu persetujuan.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>NRP</th>
                                <th>Jabatan</th>
                                <th>Departemen</th>
                                <th>Email</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingUsers as $pending)
                                <tr>
                                    <td class="fw-semibold">{{ $pending->name }}</td>
                                    <td>{{ $pending->nrp }}</td>
                                    <td>{{ $pending->jabatan ?? '—' }}</td>
                                    <td><span class="badge bg-secondary">{{ $pending->department->code ?? '—' }}</span></td>
                                    <td class="small text-muted">{{ $pending->email }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('users.approve', $pending) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Setujui</button>
                                        </form>
                                        <form method="POST" action="{{ route('users.reject', $pending) }}" class="d-inline"
                                              onsubmit="return confirm('Tolak pendaftaran {{ $pending->name }}?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Tolak</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
