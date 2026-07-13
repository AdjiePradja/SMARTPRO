<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SmartPro') — SmartPro</title>
    {{-- Bootstrap 5 + Bootstrap Icons via CDN (no Node/Vite build needed on XAMPP) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --pp-navy: #0f2b46; --pp-teal: #12707a; --pp-amber: #f5a623; }
        body {
            background: linear-gradient(135deg, var(--pp-navy) 0%, #123a5c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .auth-card { border: none; border-radius: 1rem; box-shadow: 0 1rem 3rem rgba(0,0,0,.25); }
        .auth-brand { color: var(--pp-navy); font-weight: 800; letter-spacing: -.5px; }
        .auth-brand span { color: var(--pp-teal); }
        .btn-pp { background: var(--pp-teal); border-color: var(--pp-teal); color: #fff; font-weight: 600; }
        .btn-pp:hover { background: #0d5a63; border-color: #0d5a63; color: #fff; }
        .form-control:focus { border-color: var(--pp-teal); box-shadow: 0 0 0 .2rem rgba(18,112,122,.15); }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center text-white mb-4">
                    <div class="display-6 fw-bold">Smart<span style="color:var(--pp-amber)">Pro</span></div>
                    <div class="small opacity-75">Document Generator System · PT PPA — site Adaro</div>
                </div>
                <div class="card auth-card">
                    <div class="card-body p-4 p-md-5">
                        @yield('content')
                    </div>
                </div>
                <div class="text-center text-white-50 small mt-3">
                    &copy; {{ date('Y') }} PT Putra Perkasa Abadi — Divisi ICTMD
                </div>
            </div>
        </div>
    </div>
</body>
</html>
