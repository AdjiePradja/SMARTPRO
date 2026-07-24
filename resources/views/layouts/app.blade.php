<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SmartPro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-webicon.png') }}">

    {{-- Soft UI Dashboard CSS (sudah memuat Bootstrap 5) — mengganti Bootstrap CDN, tanpa dobel --}}
    <link href="{{ asset('soft-ui/css/soft-ui-dashboard.min.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script>
        (function () { document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('pp-theme') || 'light'); })();
        function ppToggleTheme() {
            var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            localStorage.setItem('pp-theme', next);
        }
    </script>
    <style>
        /* Palet Soft UI (referensi build "orange primary") — dipakai seluruh tema:
           orange=Users/primary, info=Clicks, warning=Sales, danger=Items. */
        :root {
            --pp-navy: #0f2b46; --pp-teal: #12707a; --pp-amber: #f5a623;
            --su-orange1: #ea580c; --su-orange2: #facc15;   /* primary (Users)  */
            --su-info1: #0ea5e9;   --su-info2: #06b6d4;      /* info (Clicks)    */
            --su-warn1: #eab308;   --su-warn2: #f97316;      /* warning (Sales)  */
            --su-danger1: #ef4444; --su-danger2: #ec4899;    /* danger (Items)   */
            --su-dark1: #27272a;   --su-dark2: #18181b;      /* dark             */
        }
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }

        /* ===== Sidebar Soft UI: putih, ikon oranye, item aktif = pill gradasi oranye ===== */
        .sidenav .sidenav-header { min-height: 4.2rem; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .sidenav .sidenav-header img { max-width: 100%; max-height: 46px; height: auto; object-fit: contain; }
        .sidenav .nav-link .icon { background-image: none !important; background-color: #fff !important; }
        .sidenav .nav-link .icon i { color: var(--su-orange1); font-size: .8rem; }   /* ikon selalu oranye */
        /* Soft UI menggeser glyph ke bawah (.icon-shape i{top:11px}, .icon-sm i{top:2px})
           sehingga ikon tak pernah center di kotaknya. Netralkan agar flex-center bekerja. */
        .sidenav .icon i, .sidenav .icon-shape i, .sidenav .icon-sm i {
            position: static; top: auto; opacity: 1;
        }
        .sidenav .navbar-nav > .nav-item > .nav-link.active {
            background-image: linear-gradient(310deg, var(--su-orange1) 0%, var(--su-orange2) 100%) !important;
            box-shadow: 0 3px 6px -2px rgba(234, 88, 12, .45);
        }
        .sidenav .nav-link.active .nav-link-text { color: #fff !important; font-weight: 600; }
        .sidenav .nav-link.active .icon { background-color: #fff !important; }
        .sidenav .nav-link.active .icon i { color: var(--su-orange1) !important; }
        /* Dropdown "Dokumen Baru" — sub-item pakai pembungkus ikon (kotak) */
        .pp-caret { font-size: .7rem; transition: transform .2s ease; color: #67748e; }
        .pp-caret-open { transform: rotate(180deg); }
        .sidenav .pp-subnav { padding: .3rem .5rem; margin-bottom: 2px; color: #67748e; font-weight: 600; border-radius: .55rem; }
        .sidenav .pp-subnav .icon { width: 30px; height: 30px; min-width: 30px; padding: 0; display: flex !important; align-items: center; justify-content: center; }
        .sidenav .pp-subnav .icon i { color: var(--su-orange1); font-size: .82rem; line-height: 1; }
        /* Perataan ikon sidebar: buang margin-bottom & float bawaan .icon-shape supaya
           setiap ikon sejajar vertikal dgn teksnya (simetris di item & sub-item). */
        .sidenav .nav-link .icon { margin-bottom: 0 !important; float: none !important; flex: 0 0 auto; }
        .sidenav .nav-link { display: flex; align-items: center; }
        .sidenav .pp-subnav .nav-link-text { font-size: .8rem; }
        .sidenav .pp-subnav:hover { background: rgba(234, 88, 12, .07); }
        .sidenav .pp-subnav.active { background: rgba(234, 88, 12, .12) !important; box-shadow: none; background-image: none !important; }
        .sidenav .pp-subnav.active .nav-link-text { color: var(--su-orange1); font-weight: 700; }
        .sidenav .pp-subnav.active .icon { background-color: #fff !important; }
        .sidenav .pp-subnav.active .icon i { color: var(--su-orange1) !important; }   /* ikon tetap hidup saat aktif */

        /* Sidebar responsif: backdrop + bisa diklik di layar kecil */
        .pp-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.35); z-index: 1039; }
        @media (min-width: 1200px) { .pp-backdrop { display: none !important; } }
        .sidenav { z-index: 1045; }
        /* Navbar ikut ter-scroll (bukan sticky) */
        .navbar-main { position: relative !important; top: auto !important; }
        .sidenav .section-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; opacity: .55; padding: .6rem 1rem .25rem; }
        .btn-pp { background-image: linear-gradient(310deg, var(--su-orange1), var(--su-orange2)); color: #fff; border: none; }
        .btn-pp:hover { color: #fff; opacity: .92; }
        /* Kartu/aksen gradasi palet Soft UI (Users/Clicks/Sales/Items) */
        .bg-su-orange { background-image: linear-gradient(310deg, var(--su-orange1), var(--su-orange2)) !important; }
        .bg-su-blue   { background-image: linear-gradient(310deg, var(--su-info1), var(--su-info2)) !important; }
        .bg-su-yellow { background-image: linear-gradient(310deg, var(--su-warn1), var(--su-warn2)) !important; }
        .bg-su-pink   { background-image: linear-gradient(310deg, var(--su-danger1), var(--su-danger2)) !important; }

        /* Dark theme */
        [data-bs-theme="dark"] body { background: #14171a !important; }
        [data-bs-theme="dark"] .sidenav, [data-bs-theme="dark"] .card:not([class*='bg-gradient']), [data-bs-theme="dark"] .navbar-main { background: #1f2428 !important; }
        [data-bs-theme="dark"] .sidenav .navbar-brand span { color: #fff; }
        [data-bs-theme="dark"] .sidenav .nav-link .icon i { color: #cdd7e2; }

        @media (min-width: 1200px) { .main-content { margin-left: 17.125rem; } }
        /* Sidebar mobile: sembunyikan geser-kiri, tampil saat .pp-open (toggle hamburger).
           !important agar MENANG atas transform bawaan Soft UI (.g-sidenav-show .sidenav …)
           yang bila tidak, membuat hamburger seakan "tak berfungsi". */
        @media (max-width: 1199.98px) {
            .sidenav { transform: translateX(-110%) !important; transition: transform .2s ease; z-index: 1040; }
            .sidenav.pp-open { transform: translateX(0) !important; }
        }

        /* ===== Tabel gaya Soft UI (header uppercase kecil, tanpa bg abu) ===== */
        .table thead th, thead.table-light th {
            text-transform: uppercase; font-size: .62rem; letter-spacing: .04em;
            color: #8392ab; font-weight: 700; background: transparent !important;
            border-bottom: 1px solid #e9ecef; padding: .7rem 1rem;
        }
        .table > tbody > tr > td { border-bottom: 1px solid #f0f2f5; vertical-align: middle; padding: .7rem 1rem; }
        .table > tbody > tr:last-child > td { border-bottom: 0; }
        /* Badge status gaya Soft UI (gradient pill) — global, tanpa ubah markup */
        .badge { padding: .5em .75em; font-weight: 600; border-radius: .6rem; }
        .badge.bg-success { background-image: linear-gradient(310deg, #22c55e, #16a34a) !important; }
        .badge.bg-danger  { background-image: linear-gradient(310deg, #ef4444, #dc2626) !important; }
        .badge.bg-warning { background-image: linear-gradient(310deg, #f59e0b, #d97706) !important; color: #fff !important; }
        .badge.bg-info    { background-image: linear-gradient(310deg, #0ea5e9, #0284c7) !important; color: #fff !important; }
        .badge.bg-primary { background-image: linear-gradient(310deg, var(--pp-teal), var(--pp-navy)) !important; }
        .badge.bg-secondary { background-image: linear-gradient(310deg, #64748b, #475569) !important; }
        .badge.bg-dark    { background-image: linear-gradient(310deg, #334155, #1e293b) !important; }
        .form-control, .form-select, .input-group-text { border-radius: .6rem; }
        /* Tombol filter sejajar dgn input/dropdown. Soft UI memberi .btn{margin-bottom:1rem};
           pada baris align-items-end margin itu ikut dihitung -> tombol terdorong naik 16px.
           Nol-kan marginnya + samakan tinggi dgn .form-control-sm. */
        .btn-input-h { --bs-btn-padding-y: .25rem; --bs-btn-line-height: 1.5; --bs-btn-font-size: .75rem; margin-bottom: 0; }
        /* Tombol ikon di samping field form (+ / − / ×): kotak, tinggi mengikuti
           input di sampingnya (align-self stretch dlm flex gap-2), tanpa
           margin-bottom bawaan Soft UI — sejajar & berjarak dari kotak field. */
        .btn-field { width: 2.7rem; min-width: 2.7rem; padding: 0; display: inline-flex; align-items: center; justify-content: center; align-self: stretch; margin-bottom: 0; flex: 0 0 auto; }
        .alert { border-radius: .9rem; border: none; }
        .btn-pp { background-image: linear-gradient(310deg, var(--su-orange1), var(--su-orange2)); color: #fff; }

        [data-bs-theme="dark"] .table thead th { color: #8b98b3; border-color: #2b3138; }
        [data-bs-theme="dark"] .table > tbody > tr > td { border-color: #2b3138; }
        [data-bs-theme="dark"] .bg-white { background-color: #1f2428 !important; }
        [data-bs-theme="dark"] .text-dark { color: #e6e9ee !important; }
    </style>
    @stack('styles')
</head>
<body class="g-sidenav-show bg-gray-100">
@php
    $user = auth()->user();
    $nav = fn ($pattern) => request()->routeIs($pattern) ? 'active' : '';
    $docTypes = \App\Models\DocumentType::orderByDesc('is_active')->orderBy('code')->get(['id', 'code', 'name', 'is_active']);
    $allDepts = \App\Models\Department::orderBy('code')->get(['id', 'code', 'name']);
    $canViewAll = $user->can('document.view_all');
    // Jenis dokumen untuk submenu (SOP/IK/SP/JSA) + ikonnya.
    $jenisMenu = ['SOP' => 'bi-file-earmark-text', 'IK' => 'bi-file-earmark-ruled', 'SP' => 'bi-sliders2', 'JSA' => 'bi-shield-exclamation'];
    // Ikon khas per departemen (variasi di sub-menu, tak lagi semua "gedung").
    $deptIconMap = [
        'ENGINEERING' => 'bi-rulers', 'FWA' => 'bi-fuel-pump', 'HCGA' => 'bi-people',
        'ICTMD' => 'bi-cpu', 'PLANT' => 'bi-truck', 'PRODUKSI' => 'bi-minecart-loaded',
        'SHE' => 'bi-shield-check',
    ];
    $deptIcon = fn ($code) => $deptIconMap[strtoupper($code)] ?? 'bi-building';
@endphp

<div x-data="{ sidebar: false,
    docMenu: {{ request()->routeIs('documents.create') ? 'true' : 'false' }},
    statusMenu: {{ request()->routeIs('documents.index') || request()->routeIs('documents.staffStatus') ? 'true' : 'false' }},
    berlakuMenu: {{ request()->routeIs('documents.published') || request()->routeIs('documents.obsolete') ? 'true' : 'false' }},
    staffMenu: {{ request()->routeIs('documents.staffStatus') ? 'true' : 'false' }} }">
    {{-- Backdrop saat sidebar terbuka di layar kecil (klik untuk menutup) --}}
    <div class="pp-backdrop" x-show="sidebar" x-cloak @click="sidebar = false" x-transition.opacity></div>
    {{-- ===== Sidebar (Soft UI sidenav) ===== --}}
    <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-white"
           data-color="primary" :class="sidebar && 'pp-open'">
        <div class="sidenav-header text-center py-3 px-3">
            <a class="navbar-brand m-0 d-block" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo-web.png') }}" alt="SmartPro" style="max-width:100%;max-height:54px;height:auto">
            </a>
        </div>
        <hr class="horizontal dark mt-0 mb-2">
        <div class="collapse navbar-collapse w-auto h-auto" style="overflow-y:auto;max-height:calc(100vh - 8rem)">
            <ul class="navbar-nav">
                @php
                    $item = function ($route, $label, $icon, $active) {
                        return '<li class="nav-item"><a class="nav-link '.$active.'" href="'.$route.'">'
                            .'<div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi '.$icon.'"></i></div>'
                            .'<span class="nav-link-text ms-1">'.$label.'</span></a></li>';
                    };
                    // Item sub-menu (pembungkus ikon kotak) — dipakai dropdown jenis/dept.
                    $subItem = function ($url, $label, $icon, $active = '') {
                        return '<li class="nav-item"><a class="nav-link pp-subnav d-flex align-items-center '.$active.'" href="'.$url.'">'
                            .'<div class="icon icon-shape shadow-sm border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi '.$icon.'"></i></div>'
                            .'<span class="nav-link-text">'.$label.'</span></a></li>';
                    };
                @endphp
                {!! $item(route('dashboard'), 'Dashboard', 'bi-speedometer2', $nav('dashboard')) !!}

                <div class="section-label">Dokumen</div>
                @can('document.create')
                    {{-- Dokumen Baru: dropdown jenis (SOP, IK, SP, JSA) --}}
                    <li class="nav-item">
                        <a class="nav-link {{ $nav('documents.create') }}" href="#" @click.prevent="docMenu = !docMenu" :aria-expanded="docMenu.toString()">
                            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-file-earmark-plus"></i></div>
                            <span class="nav-link-text ms-1">Dokumen Baru</span>
                            <i class="bi bi-chevron-down ms-auto pp-caret" :class="docMenu && 'pp-caret-open'"></i>
                        </a>
                        <div x-show="docMenu" x-cloak x-transition.opacity>
                            <ul class="nav flex-column ms-3 ps-2 my-1">
                                @foreach (['SOP' => ['Standard Operating Procedure', 'bi-file-earmark-text'], 'IK' => ['Instruksi Kerja', 'bi-file-earmark-ruled'], 'SP' => ['Standar Produksi', 'bi-sliders2'], 'JSA' => ['Job Safety Analysis', 'bi-shield-exclamation']] as $code => $meta)
                                    @php $subActive = request()->routeIs('documents.create') && strtoupper(request('type', 'SOP')) === $code; @endphp
                                    <li class="nav-item">
                                        <a class="nav-link pp-subnav d-flex align-items-center {{ $subActive ? 'active' : '' }}" href="{{ route('documents.create', ['type' => $code]) }}" title="{{ $meta[0] }}">
                                            <div class="icon icon-shape shadow-sm border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi {{ $meta[1] }}"></i></div>
                                            <span class="nav-link-text">{{ $code }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                    {!! $item(route('documents.revisi'), 'Dokumen Revisi', 'bi-arrow-counterclockwise', $nav('documents.revisi')) !!}
                @endcan

                {{-- Status Dokumen: HANYA untuk GL (pembuat) & Non-Staff (read-only).
                     SH/DH/PJO memakai "Status Dokumen Staff" (v2 rev: hapus Status Dokumen
                     di SH/DH/PJO agar tidak dobel). --}}
                @if (! $user->can('document.review') && ! $canViewAll)
                    @if ($user->can('document.create'))
                        {{-- GL: dropdown 2 submenu — "Dokumen Departemen" (se-dept, read-only)
                             & "Dokumen Saya" (buatannya, bisa hapus draft). --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $nav('documents.index') }}{{ $nav('documents.staffStatus') }}" href="#" @click.prevent="statusMenu = !statusMenu" :aria-expanded="statusMenu.toString()">
                                <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-list-check"></i></div>
                                <span class="nav-link-text ms-1">Status Dokumen</span>
                                <i class="bi bi-chevron-down ms-auto pp-caret" :class="statusMenu && 'pp-caret-open'"></i>
                            </a>
                            <div x-show="statusMenu" x-cloak x-transition.opacity>
                                <ul class="nav flex-column ms-3 ps-2 my-1">
                                    {!! $subItem(route('documents.staffStatus'), 'Dokumen Departemen', 'bi-building', $nav('documents.staffStatus')) !!}
                                    {!! $subItem(route('documents.index'), 'Dokumen Saya', 'bi-person-lines-fill', $nav('documents.index')) !!}
                                </ul>
                            </div>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ $nav('documents.index') }}" href="#" @click.prevent="statusMenu = !statusMenu" :aria-expanded="statusMenu.toString()">
                                <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-list-check"></i></div>
                                <span class="nav-link-text ms-1">Status Dokumen</span>
                                <i class="bi bi-chevron-down ms-auto pp-caret" :class="statusMenu && 'pp-caret-open'"></i>
                            </a>
                            <div x-show="statusMenu" x-cloak x-transition.opacity>
                                <ul class="nav flex-column ms-3 ps-2 my-1">
                                    {!! $subItem(route('documents.index'), 'Semua', 'bi-grid', request()->routeIs('documents.index') && ! request('type') ? 'active' : '') !!}
                                    @foreach ($jenisMenu as $code => $icon)
                                        {!! $subItem(route('documents.index', ['type' => $code]), $code, $icon, strtoupper(request('type', '')) === $code ? 'active' : '') !!}
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endif
                @endif

                {{-- Dokumen Berlaku (+ sub-menu Tidak Berlaku bagi SH/DH/PJO/Admin;
                     GL ikut melihat read-only, v3 rev) --}}
                @php $canObsolete = $user->can('document.request_revision') || $canViewAll || $user->can('document.create'); @endphp
                @if ($canObsolete)
                    <li class="nav-item">
                        <a class="nav-link {{ $nav('documents.published') }}{{ $nav('documents.obsolete') }}" href="#" @click.prevent="berlakuMenu = !berlakuMenu" :aria-expanded="berlakuMenu.toString()">
                            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-folder-check"></i></div>
                            <span class="nav-link-text ms-1">Dokumen Berlaku</span>
                            <i class="bi bi-chevron-down ms-auto pp-caret" :class="berlakuMenu && 'pp-caret-open'"></i>
                        </a>
                        <div x-show="berlakuMenu" x-cloak x-transition.opacity>
                            <ul class="nav flex-column ms-3 ps-2 my-1">
                                {!! $subItem(route('documents.published'), 'Berlaku', 'bi-folder-check', $nav('documents.published')) !!}
                                {!! $subItem(route('documents.obsolete'), 'Tidak Berlaku', 'bi-slash-circle', $nav('documents.obsolete')) !!}
                            </ul>
                        </div>
                    </li>
                @else
                    {!! $item(route('documents.published'), 'Dokumen Berlaku', 'bi-folder-check', $nav('documents.published')) !!}
                @endif

                @can('document.review')
                    <div class="section-label">Peninjauan</div>
                    {!! $item(route('review.index'), 'Tinjau Dokumen', 'bi-clipboard-check', $nav('review.*')) !!}
                @endcan

                {{-- Status Dokumen Staff: SH/DH (dept sendiri, read-only) 2d; PJO (7 dept submenu) 2e --}}
                @if ($user->can('document.review') || $canViewAll)
                    @if ($canViewAll)
                        <li class="nav-item">
                            <a class="nav-link {{ $nav('documents.staffStatus') }}" href="#" @click.prevent="staffMenu = !staffMenu" :aria-expanded="staffMenu.toString()">
                                <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-people-fill"></i></div>
                                <span class="nav-link-text ms-1">Status Dokumen Staff</span>
                                <i class="bi bi-chevron-down ms-auto pp-caret" :class="staffMenu && 'pp-caret-open'"></i>
                            </a>
                            <div x-show="staffMenu" x-cloak x-transition.opacity>
                                <ul class="nav flex-column ms-3 ps-2 my-1">
                                    @foreach ($allDepts as $d)
                                        {!! $subItem(route('documents.staffStatus', ['department_id' => $d->id]), $d->code, $deptIcon($d->code), request('department_id') == $d->id ? 'active' : '') !!}
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @else
                        {!! $item(route('documents.staffStatus'), 'Status Dokumen Staff', 'bi-people-fill', $nav('documents.staffStatus')) !!}
                    @endif
                @endif
                @can('document.approve')
                    {!! $item(route('approvals.index'), 'Persetujuan Saya', 'bi-patch-check', $nav('approvals.*')) !!}
                @endcan

                @canany(['user.manage','user.approve_registration','audit.view'])
                    <div class="section-label">Administrasi</div>
                    @can('user.approve_registration'){!! $item(route('users.pending'), 'Persetujuan Akun', 'bi-person-check', $nav('users.pending')) !!}@endcan
                    @can('user.manage'){!! $item(route('users.index'), 'Manajemen User', 'bi-people', $nav('users.index').' '.$nav('users.create')) !!}@endcan
                    @can('audit.view'){!! $item(route('audit.index'), 'Audit Log', 'bi-shield-lock', $nav('audit.index')) !!}@endcan
                @endcanany

                <div class="section-label">Informasi</div>
                {!! $item(route('account.info'), 'Informasi Akun', 'bi-person-badge', $nav('account.info')) !!}
            </ul>
        </div>
    </aside>

    {{-- ===== Main content ===== --}}
    <main class="main-content position-relative border-radius-lg">
        <nav class="navbar navbar-main navbar-expand-lg mx-3 mt-3 px-3 py-2 shadow-sm border-radius-xl bg-white">
            <div class="d-flex align-items-center w-100">
                <button type="button" class="btn btn-link text-dark d-xl-none p-0 me-2 d-flex align-items-center lh-1" @click="sidebar = !sidebar" aria-label="Menu"><i class="bi bi-list fs-3"></i></button>
                <h6 class="mb-0 fw-bold text-dark lh-1">@yield('title', 'Dashboard')</h6>
                <div class="ms-auto d-flex align-items-center gap-4">
                    <button class="btn btn-link text-secondary p-0 fs-4 lh-1" onclick="ppToggleTheme()" title="Ganti tema"><i class="bi bi-circle-half"></i></button>

                    @php $ppUnread = $user->unreadNotifications; @endphp
                    <div class="dropdown">
                        <a href="#" class="position-relative text-secondary" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-4"></i>
                            @if($ppUnread->count())<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem">{{ $ppUnread->count() }}</span>@endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:330px;max-height:420px;overflow:auto">
                            <li class="d-flex justify-content-between align-items-center px-3 py-2">
                                <span class="fw-semibold small">Notifikasi</span>
                                @if($ppUnread->count())<form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn btn-link btn-sm p-0 text-decoration-none">Tandai dibaca</button></form>@endif
                            </li>
                            <li><hr class="dropdown-divider my-0"></li>
                            @forelse($user->notifications->take(8) as $n)
                                {{-- Belum dibaca = latar netral + titik penanda; sudah dibaca = polos --}}
                                <li><a class="dropdown-item d-flex gap-2 py-2 align-items-start {{ $n->read_at ? '' : 'bg-body-secondary fw-semibold' }}" href="{{ route('notifications.open', $n->id) }}">
                                    <i class="bi {{ $n->data['icon'] ?? 'bi-bell' }} mt-1"></i>
                                    <span class="small text-wrap flex-grow-1">{{ $n->data['message'] ?? '' }}<br><span class="text-muted fw-normal" style="font-size:.7rem">{{ $n->created_at->diffForHumans() }}</span></span>
                                    @unless($n->read_at)<span class="rounded-circle mt-1" style="width:8px;height:8px;background:#8392ab;flex:0 0 auto"></span>@endunless
                                </a></li>
                            @empty
                                <li><span class="dropdown-item-text text-muted small text-center d-block py-3">Belum ada notifikasi</span></li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown">
                            <div class="text-end me-2 d-none d-sm-block">
                                <div class="fw-semibold text-dark small lh-1">{{ $user->name }}</div>
                                <span class="text-xs text-secondary">{{ str_replace('_',' ', $user->getRoleNames()->first() ?? '-') }}</span>
                            </div>
                            @if ($user->photoUrl())
                                <img src="{{ $user->photoUrl() }}" class="rounded-circle object-fit-cover" style="width:36px;height:36px" alt="{{ $user->name }}">
                            @else
                                <i class="bi bi-person-circle fs-4 text-secondary"></i>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('account.info') }}"><i class="bi bi-person-badge"></i> Informasi Akun</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button></form></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid py-4">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('status') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Validasi "semua field wajib terisi" di sisi klien: tandai field kosong MERAH
    // (is-invalid), gulir + fokus ke yang pertama, tampilkan pesan (bukan alert
    // browser "…says"). Field opsional diberi atribut `data-optional`. Hanya field
    // yang TERLIHAT (langkah aktif) yang dicek; kelengkapan antar-langkah dijaga
    // server. Return true bila semua terisi.
    function ppValidateRequired(form) {
        const els = form.querySelectorAll('input:not([type=hidden]):not([type=file]):not([type=checkbox]):not([type=radio]):not([type=button]):not([type=submit]), textarea, select');
        let first = null;
        els.forEach(function (el) {
            if (el.disabled || el.dataset.optional !== undefined || el.offsetParent === null) return;
            el.classList.remove('is-invalid');
            if (!String(el.value).trim()) { el.classList.add('is-invalid'); if (!first) first = el; }
        });
        if (first) {
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(function () { try { first.focus({ preventScroll: true }); } catch (e) {} }, 250);
            Swal.fire({ icon: 'error', title: 'Ada kolom yang belum diisi',
                text: 'Lengkapi semua kolom yang ditandai merah sebelum melanjutkan.', confirmButtonColor: '#ea580c' });
            return false;
        }
        return true;
    }
    // Bersihkan tanda merah begitu field diisi.
    document.addEventListener('input', function (e) {
        if (e.target.classList && e.target.classList.contains('is-invalid') && String(e.target.value).trim()) {
            e.target.classList.remove('is-invalid');
        }
    });
</script>
<script>
    // Konfirmasi SweetAlert untuk form ber-`data-confirm`. Sumber atribut = TOMBOL
    // yang diklik (e.submitter) bila ia punya data-confirm (mendukung 2 tombol beda
    // pesan dalam 1 form, mis. Setujui/Kembalikan), jika tidak → form.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        const btn = e.submitter;
        const src = (btn && btn.hasAttribute('data-confirm')) ? btn : form;
        const msg = src.getAttribute('data-confirm');
        if (!msg || form.dataset.confirmed) return;
        e.preventDefault();
        Swal.fire({
            title: src.getAttribute('data-confirm-title') || 'Konfirmasi', text: msg,
            icon: src.getAttribute('data-confirm-icon') || 'question', showCancelButton: true,
            confirmButtonText: src.getAttribute('data-confirm-ok') || 'Ya, lanjutkan', cancelButtonText: 'Batal',
            confirmButtonColor: '#ea580c', cancelButtonColor: '#6c757d',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            form.dataset.confirmed = '1';
            // Klik ulang tombol agar name/value-nya (mis. decision=approve) ikut terkirim.
            if (btn) { btn.click(); } else { form.submit(); }
        });
    }, true);
</script>
@stack('scripts')
</body>
</html>
