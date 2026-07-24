@extends('layouts.app')
@section('title', 'Informasi Akun')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-body mb-0">Informasi Akun</h1>
        <p class="text-muted small mb-0">Kelola foto profil, nomor HP, dan email Anda.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data"
          x-data="{ preview: '{{ $user->photoUrl() ?? '' }}', remove: false }">
        @csrf
        @method('PUT')

        <div class="row g-3">
            {{-- Kartu avatar (kiri) --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-4">
                        <div class="d-inline-block">
                            <template x-if="preview && !remove">
                                <img :src="preview" class="rounded-circle shadow object-fit-cover" style="width:110px;height:110px" alt="Foto profil">
                            </template>
                            <template x-if="!preview || remove">
                                <div class="rounded-circle shadow bg-gradient-primary d-inline-flex align-items-center justify-content-center" style="width:110px;height:110px">
                                    <span class="text-white fw-bold" style="font-size:2.4rem">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                </div>
                            </template>
                        </div>

                        <h2 class="h5 fw-bold mt-3 mb-0">{{ $user->name }}</h2>
                        <div class="text-muted small">{{ \App\Models\User::JABATAN_LABELS[$user->jabatan] ?? ($user->getRoleNames()->first() ?? '-') }}</div>
                        <span class="badge bg-{{ $user->status === 'active' ? 'success' : 'warning' }} mt-2 text-capitalize">{{ $user->status }}</span>

                        <div class="mt-3 text-start">
                            <label class="form-label small fw-semibold">Ganti Foto Profil</label>
                            <input type="file" name="photo" accept="image/jpeg,image/png" class="form-control form-control-sm"
                                   @change="const f=$event.target.files[0]; if(f){ preview=URL.createObjectURL(f); remove=false; }">
                            <div class="form-text">JPG/PNG, maks 2MB.</div>
                            @if ($user->photo_path)
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="removePhoto" x-model="remove">
                                    <label class="form-check-label small text-danger" for="removePhoto">Hapus foto profil</label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detail + field yang bisa diedit (kanan) --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-card-list"></i> Detail Akun</div>
                    <div class="card-body">
                        {{-- Read-only (dikelola admin) --}}
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">NRP</dt><dd class="col-sm-8 font-monospace">{{ $user->nrp ?? '—' }}</dd>
                            <dt class="col-sm-4 text-muted">Nama Lengkap</dt><dd class="col-sm-8">{{ $user->name }}</dd>
                            <dt class="col-sm-4 text-muted">Jabatan</dt><dd class="col-sm-8">{{ \App\Models\User::JABATAN_LABELS[$user->jabatan] ?? '—' }}</dd>
                            <dt class="col-sm-4 text-muted">Departemen</dt><dd class="col-sm-8">{{ $user->department?->name ?? '— (lintas departemen)' }}</dd>
                            <dt class="col-sm-4 text-muted">Peran Sistem</dt><dd class="col-sm-8 text-capitalize">{{ str_replace('_', ' ', $user->getRoleNames()->first() ?? '—') }}</dd>
                        </dl>

                        <hr class="my-3">

                        {{-- Editable oleh user sendiri --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nomor HP</label>
                            <input type="text" name="nomor_hp" value="{{ old('nomor_hp', $user->nomor_hp) }}" class="form-control" placeholder="mis. 0812xxxxxxx">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" placeholder="nama@perusahaan.com">
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-pp"><i class="bi bi-save"></i> Simpan Perubahan</button>
                            <a href="{{ route('dashboard') }}" class="btn btn-light">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <style>[x-cloak]{display:none!important}</style>
@endsection
