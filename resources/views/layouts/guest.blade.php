<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SmartPro') — SmartPro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-webicon.png') }}">
    <link href="{{ asset('soft-ui/css/soft-ui-dashboard.min.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --su-orange1: #ea580c; --su-orange2: #facc15; --pp-navy: #0f2b46; }
        body { font-family: 'Inter', sans-serif; }
        .auth-brand { color: var(--pp-navy); font-weight: 800; letter-spacing: -.5px; }
        .auth-brand span { color: var(--su-orange1); }
        .btn-pp { background-image: linear-gradient(310deg, var(--su-orange1), var(--su-orange2)); color: #fff; border: none; font-weight: 600; }
        .btn-pp:hover { color: #fff; opacity: .93; }
        /* Teks solid (bukan gradien) agar mudah dibaca — UX lebih baik */
        .text-gradient.text-pp { color: var(--su-orange1); }
    </style>
</head>
<body class="bg-gray-100">
    <main class="main-content mt-0">
        <section>
            <div class="page-header min-vh-100">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-4 col-lg-5 col-md-6 d-flex flex-column mx-auto">
                            <div class="card card-plain mt-6">
                                <div class="card-header pb-0 text-center bg-transparent">
                                    <img src="{{ asset('images/logo-web.png') }}" alt="SmartPro" style="max-width:190px;height:auto" class="mb-3">
                                    <p class="mb-0 text-sm text-secondary">Document Generator System · PT PPA — site Adaro</p>
                                </div>
                                <div class="card-body">
                                    @yield('content')
                                </div>
                                <div class="card-footer text-center pt-0 px-lg-2 px-1">
                                    <p class="text-xs text-secondary mb-0">&copy; {{ date('Y') }} PT Putra Perkasa Abadi — Divisi ICTMD</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="oblique position-absolute top-0 h-100 d-md-block d-none me-n8">
                                <div class="oblique-image bg-cover position-absolute fixed-top ms-auto h-100 z-index-0 ms-n6"
                                     style="background-image:url('{{ asset('soft-ui/img/curved-images/curved6.jpg') }}')"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
