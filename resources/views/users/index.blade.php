@extends('layouts.app')
@section('title', 'Manajemen User')

@php
    $roleLabels = [
        'admin_it' => ['Admin IT', 'danger'],
        'pimpinan' => ['Pimpinan', 'primary'],
        'section_head' => ['Section Head', 'info'],
        'group_leader' => ['Group Leader', 'success'],
        'staff' => ['Staff', 'secondary'],
    ];
    $statusLabels = ['active' => 'success', 'pending' => 'warning', 'rejected' => 'danger'];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">Manajemen User</h1>
            <p class="text-muted small mb-0">Kelola semua akun dan buat akun staf.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Buat Akun Staf</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Cari (nama / NRP / email)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Departemen</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(($filters['department_id'] ?? '') == $dept->id)>{{ $dept->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-sm btn-secondary w-100"><i class="bi bi-search"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th><th>NRP</th><th>No. HP</th><th>Departemen</th><th>Peran</th><th>Status</th><th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $u)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $u->name }}</div>
                                    <div class="small text-muted"><i class="bi bi-person-vcard"></i> {{ $u->nrp }}</div>
                                </td>
                                <td>{{ $u->nrp ?? '—' }}</td>
                                <td class="small">{{ $u->nomor_hp ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $u->department->code ?? '—' }}</span></td>
                                <td>
                                    @php $r = $u->getRoleNames()->first(); [$lbl,$clr] = $roleLabels[$r] ?? [$r,'secondary']; @endphp
                                    <span class="badge bg-{{ $clr }}">{{ $lbl }}</span>
                                </td>
                                <td><span class="badge bg-{{ $statusLabels[$u->status] ?? 'secondary' }}-subtle text-{{ $statusLabels[$u->status] ?? 'secondary' }}-emphasis text-capitalize">{{ $u->status }}</span></td>
                                <td class="text-end">
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.toggleStatus', $u) }}" class="d-inline"
                                              onsubmit="return confirm('Ubah status akun {{ $u->name }}?')">
                                            @csrf
                                            @if ($u->status === 'active')
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle"></i> Nonaktifkan</button>
                                            @else
                                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i> Aktifkan</button>
                                            @endif
                                        </form>
                                    @else
                                        <span class="badge bg-light text-muted">Akun Anda</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada user ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer bg-white">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
