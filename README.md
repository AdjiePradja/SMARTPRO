<!-- <p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). -->

# SMARTPRO — Document Generator PT PPA

SMARTPRO adalah aplikasi internal PT Putra Perkasa Abadi untuk menghasilkan dokumen mutu (SOP, SP, IK, JSA) berdasarkan kebutuhan departemen. Aplikasi dibuat dengan Laravel 12, Bootstrap 5, Alpine.js, dan MySQL.

## Teknologi

- PHP 8.2+
- Laravel 12
- Blade + Bootstrap 5
- Alpine.js
- MySQL 8
- spatie/laravel-permission
- barryvdh/laravel-dompdf
- Bootstrap Icons

## Fitur Utama

- Manajemen dokumen SOP/SP/IK/JSA
- Form generator berbasis schema JSON
- Otorisasi role/jabatan
- Workflows review dan approval
- Preview dan export PDF
- Penyimpanan lampiran foto per departemen

## Struktur Kode Penting

- `app/Http/Controllers`
- `app/Models`
- `app/Services`
- `app/Policies`
- `app/Http/Requests`
- `resources/views/{jenis}`
- `database/migrations`
- `docs/`
- `public/images/logo-ppa`

## Konvensi Proyek

- Controller dan model mengikuti standar Laravel.
- Blade berada di `resources/views/{jenis}/{aksi}.blade.php`.
- Route bernama `sop.index`, `sop.create`, `sop.edit`, dll.
- Logika bisnis berada di `app/Services`.
- Validasi menggunakan Form Request.
- Otorisasi menggunakan Policy/Gate.
- Data dokumen disimpan dalam JSON untuk setiap section, tidak menggunakan kolom statis per jenis.

## Setup Lokal

1. Salin `.env.example` menjadi `.env`
2. Atur database MySQL di `.env`
3. Jalankan:
   - `composer install`
   - `php artisan key:generate`
   - `php artisan migrate`
   - `php artisan storage:link`
4. Jalankan development server:
   - `php artisan serve`

## Catatan Penting

- Jangan commit file `.env`.
- Jangan gunakan `migrate:fresh` atau perintah yang menghapus data tanpa izin.
- Ikuti PRD dan `docs/CLAUDE.md` untuk aturan fitur dan alur kerja.
- Perubahan besar harus dilakukan satu fase/tugas, lalu berhenti dan minta review.

## Dokumentasi

- `docs/PRD-SmartPro-v2.0.md`
- `docs/INSTRUKSI-Merging-Berfase.md`
- `docs/schema-sop.json`
- `docs/Dokumen SOP contoh`
