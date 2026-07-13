<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SmartPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- Alpine.js for lightweight interactivity (wizard/preview) — no SPA framework (CLAUDE.md §2) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    {{-- Theme: light default + dark toggle (persisted). Applied early to avoid flash. --}}
    <script>
        (function () { document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('pp-theme') || 'light'); })();
        function ppToggleTheme() {
            var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            localStorage.setItem('pp-theme', next);
        }
    </script>
    <style>
        :root { --pp-navy: #0f2b46; --pp-teal: #12707a; --pp-amber: #f5a623; }
        body { background: #f4f6f9; }
        .pp-sidebar {
            width: 250px; min-height: 100vh; background: var(--pp-navy); color: #cdd7e2;
            position: fixed; top: 0; left: 0; z-index: 1030;
        }
        .pp-sidebar .brand { color: #fff; font-weight: 800; letter-spacing: -.5px; }
        .pp-sidebar .brand span { color: var(--pp-amber); }
        .pp-sidebar .nav-link { color: #cdd7e2; border-radius: .5rem; margin: .1rem .5rem; padding: .55rem .85rem; }
        .pp-sidebar .nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
        .pp-sidebar .nav-link.active { background: var(--pp-teal); color: #fff; }
        .pp-sidebar .nav-link i { width: 1.4rem; }
        .pp-sidebar .section-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; color: #6f8299; padding: .75rem 1rem .25rem; }
        .pp-main { margin-left: 250px; }
        .pp-topbar { background: #fff; border-bottom: 1px solid #e3e8ef; }
        .role-badge { background: var(--pp-teal); }
        @media (max-width: 768px) { .pp-sidebar { transform: translateX(-100%); transition: .2s; } .pp-sidebar.open { transform: none; } .pp-main { margin-left: 0; } }
        [x-cloak] { display: none !important; }
        /* Dark theme overrides for custom (non-Bootstrap) surfaces */
        [data-bs-theme="dark"] body { background: #14171a; }
        [data-bs-theme="dark"] .pp-topbar { background: #1f2428 !important; border-color: #2b3138 !important; }
        [data-bs-theme="dark"] .pp-topbar .text-secondary { color: #cdd7e2 !important; }
    </style>
    @stack('styles')
</head>
<body>
@php
    $user = auth()->user();
    $nav = function ($pattern) { return request()->routeIs($pattern) ? 'active' : ''; };
    $docTypes = \App\Models\DocumentType::orderByDesc('is_active')->orderBy('code')->get(['id', 'code', 'name', 'is_active']);
@endphp

<div x-data="{ sidebar: false, docMenu: {{ request()->routeIs('documents.create') ? 'true' : 'false' }} }">
    {{-- Sidebar --}}
    <nav class="pp-sidebar" :class="sidebar && 'open'">
        <div class="p-3 border-bottom border-secondary border-opacity-25">
            <div class="brand fs-4">Smart<span>Pro</span></div>
            <div class="small text-white-50">Document Generator</div>
        </div>
        <ul class="nav flex-column py-2">
            <li><a href="{{ route('dashboard') }}" class="nav-link {{ $nav('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>

            <div class="section-label">Dokumen</div>
            @can('document.create')
            <li><a href="{{ route('documents.create') }}" class="nav-link {{ $nav('documents.create') }}"><i class="bi bi-file-earmark-plus"></i> Dokumen Baru</a></li>
            @endcan
            <li><a href="{{ route('documents.index') }}" class="nav-link {{ $nav('documents.index') }} {{ $nav('documents.edit') }}"><i class="bi bi-list-check"></i> Status Dokumen Saya</a></li>
            <li><a href="{{ route('documents.published') }}" class="nav-link {{ $nav('documents.published') }}"><i class="bi bi-folder-check"></i> Dokumen Berlaku</a></li>
            @can('document.create')
            <li><a href="{{ route('documents.revisi') }}" class="nav-link {{ $nav('documents.revisi') }}"><i class="bi bi-arrow-counterclockwise"></i> Dokumen Revisi</a></li>
            @endcan

            @can('document.review')
            <div class="section-label">Peninjauan</div>
            <li><a href="{{ route('review.index') }}" class="nav-link {{ $nav('review.*') }}"><i class="bi bi-clipboard-check"></i> Tinjau Dokumen</a></li>
            @endcan

            @can('document.approve')
            <li><a href="{{ route('approvals.index') }}" class="nav-link {{ $nav('approvals.*') }}"><i class="bi bi-patch-check"></i> Persetujuan Saya</a></li>
            @endcan

            @canany(['user.manage','user.approve_registration'])
            <div class="section-label">Administrasi</div>
            <li><a href="{{ route('users.pending') }}" class="nav-link {{ $nav('users.pending') }}"><i class="bi bi-person-check"></i> Persetujuan Akun</a></li>
            @endcanany
            @can('user.manage')
            <li><a href="{{ route('users.index') }}" class="nav-link {{ $nav('users.index') }} {{ $nav('users.create') }}"><i class="bi bi-people"></i> Manajemen User</a></li>
            @endcan
            @can('audit.view')
            <li><a href="#" class="nav-link"><i class="bi bi-shield-lock"></i> Audit Log</a></li>
            @endcan
        </ul>
    </nav>

    {{-- Main --}}
    <div class="pp-main">
        <div class="pp-topbar d-flex align-items-center justify-content-between px-3 py-2 sticky-top">
            <button class="btn btn-sm btn-light d-md-none" @click="sidebar = !sidebar"><i class="bi bi-list"></i></button>
            <div class="fw-semibold text-secondary">@yield('title', 'Dashboard')</div>
            <div class="d-flex align-items-center gap-2">
                {{-- Theme toggle (terang/gelap) --}}
                <button class="btn btn-sm btn-outline-secondary border-0 fs-5 py-0" onclick="ppToggleTheme()" title="Ganti tema"><i class="bi bi-circle-half"></i></button>

                {{-- Notifikasi lonceng --}}
                @php $ppUnread = $user->unreadNotifications; @endphp
                <div class="dropdown">
                    <a href="#" class="position-relative text-secondary text-decoration-none" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        @if($ppUnread->count())<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.55rem">{{ $ppUnread->count() }}</span>@endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:330px;max-height:420px;overflow:auto">
                        <li class="d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="fw-semibold small">Notifikasi</span>
                            @if($ppUnread->count())<form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn btn-link btn-sm p-0 text-decoration-none">Tandai dibaca</button></form>@endif
                        </li>
                        <li><hr class="dropdown-divider my-0"></li>
                        @forelse($user->notifications->take(8) as $n)
                            <li>
                                <a class="dropdown-item d-flex gap-2 py-2 {{ $n->read_at ? '' : 'bg-primary bg-opacity-10' }}" href="{{ route('notifications.open', $n->id) }}">
                                    <i class="bi {{ $n->data['icon'] ?? 'bi-bell' }} mt-1"></i>
                                    <span class="small text-wrap">{{ $n->data['message'] ?? '' }}<br><span class="text-muted" style="font-size:.7rem">{{ $n->created_at->diffForHumans() }}</span></span>
                                </a>
                            </li>
                        @empty
                            <li><span class="dropdown-item-text text-muted small text-center d-block py-3">Belum ada notifikasi</span></li>
                        @endforelse
                    </ul>
                </div>

                {{-- Profil --}}
                <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="text-end me-2 d-none d-sm-block">
                        <div class="fw-semibold text-dark small">{{ $user->name }}</div>
                        <span class="badge role-badge text-white" style="font-size:.65rem">{{ str_replace('_',' ', $user->getRoleNames()->first() ?? '-') }}</span>
                    </div>
                    <i class="bi bi-person-circle fs-4 text-secondary"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted">{{ $user->email }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button>
                        </form>
                    </li>
                </ul>
                </div>{{-- /profil --}}
            </div>{{-- /right cluster --}}
        </div>

        <main class="p-3 p-md-4">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('status') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
