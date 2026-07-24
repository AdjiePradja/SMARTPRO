# Bekal Migrasi Format PDF Dokumen → Project SMARTPRO

Referensi lengkap untuk mereplikasi generator PDF dokumen (JSA, SOP, IK, SP)
dari project **Sistem_managemen_dokumen_prosedur** ke project **smartpro**.
Semua PDF dibuat memakai **barryvdh/laravel-dompdf**.

---

## 1. Ringkasan Arsitektur

Tiap jenis dokumen punya pola sama:

```
Controller  →  bangun array $data/$pdfData  →  Pdf::loadView('template', $data)
            →  $pdf->output()  →  Storage::put('public/<jenis>_pending/<file>.pdf')
```

| Jenis | Template Blade                                   | Controller                                             | Folder storage        |
|-------|--------------------------------------------------|--------------------------------------------------------|-----------------------|
| JSA   | `admin.Menus.Template.template_jsa`              | `App\Http\Controllers\DokumenBaru\tb_jsa_baru`         | `public/jsa_pending`  |
| SOP   | `admin.Menus.Template.template_sop`              | `App\Http\Controllers\DokumenBaru\tb_sop_baru`         | `public/sop_pending`  |
| IK    | `admin.Menus.Template.template_ik`               | `App\Http\Controllers\DokumenBaru\tb_ik_baru`          | `public/ik_pending`   |
| SP    | `admin.Menus.Template.template_sp`               | `App\Http\Controllers\DokumenBaru\tb_sp_baru`          | `public/sp_pending`   |

Import wajib di setiap controller: `use Barryvdh\DomPDF\Facade\Pdf;`

---

## 2. File yang HARUS disalin ke smartpro

### A. Template Blade (INI penentu format visual — paling penting)
- `resources/views/admin/Menus/Template/template_jsa.blade.php`
- `resources/views/admin/Menus/Template/template_sop.blade.php`
- `resources/views/admin/Menus/Template/template_ik.blade.php`
- `resources/views/admin/Menus/Template/template_sp.blade.php`

### B. Aset gambar (di-embed base64 di template — WAJIB ada)
- `public/assets/img/LogoPPA.png`   → logo header
- `public/assets/img/Approved.png`  → stempel pengesahan

### C. Dependency & konfigurasi
- Package: `composer require barryvdh/laravel-dompdf`
- Font **DejaVu Sans** (bawaan dompdf) — dipakai untuk karakter centang `✔` di JSA.
- Tidak ada `config/dompdf.php` custom di project ini → memakai default vendor. Cukup pakai default.

### D. Storage
- Buat folder: `storage/app/public/{jsa_pending, sop_pending, ik_pending, sp_pending}`
- Jalankan: `php artisan storage:link`

### E. Struktur data (model + migration) untuk JSA
JSA butuh 4 tabel berelasi (steps → hazards → controls):
- `app/Models/DokumenBaru/JSA/jsa_baru.php`      (hasMany steps, FK `jsa_id`)
- `app/Models/DokumenBaru/JSA/jsa_step_baru.php` (hasMany hazards, FK `jsa_step_id`)
- `app/Models/DokumenBaru/JSA/jsa_hazard_baru.php`(hasMany controls, FK `jsa_hazard_id`)
- `app/Models/DokumenBaru/JSA/jsa_control_baru.php`
- Migration terkait: `create_jsa_barus_table`, `create_jsa_step_barus_table`,
  `create_jsa_hazard_barus_table`, `create_jsa_control_barus_table`,
  `add_approval_columns_to_jsa_barus_table`.

---

## 3. Kontrak Variabel per Template
Kalau salah satu variabel di bawah tidak dikirim / beda nama, hasil PDF akan rusak/kosong.

### 3.1 JSA — `template_jsa.blade.php`
Dikirim dari `tb_jsa_baru::generateJsaPdf()`:
```php
$jsa = jsa_baru::with(['steps.hazards.controls'])->findOrFail($jsaId);
$data = [
    'jsa'              => $jsa,
    'jabatanPembuat'   => $this->jabatanName($jsa->dibuat_oleh),
    'jabatanReviewer'  => $this->jabatanName($jsa->direview_oleh),
    'jabatanPenyetuju' => $this->jabatanName($jsa->disetujui_oleh),
];
```
Kolom `$jsa` yang dipakai template:
`no_jsa, no_dokumen, tgl_pembuatan, nama_pekerjaan, departemen, lokasi_kerja,
apd_wajib, peralatan_pendukung, dibuat_oleh, direview_oleh, disetujui_oleh,
direview_oleh_approve, disetujui_oleh_approve`
Relasi: `steps[].{step_no, description}` → `hazards[].{hazard_no, hazard_description}`
→ `controls[].{control_no, control_description, approved}`

Format kunci: **A4 landscape**, `table-layout: fixed`, kolom 20/20/50/10%,
rowspan otomatis dari jumlah controls, centang `✔` pakai `font-family: DejaVu Sans`.

### 3.2 SOP — `template_sop.blade.php` (paling lengkap, approval 3 tahap)
Data = `$doc->toArray()` + tambahan. Variabel dipakai template:
- Header: `no_dokumen, revisi, edisi, judul, efektif_date, pjo_date`
- Isi: `tujuan, ruang_lingkup, referensi, definisi, aktifitas_tanggung_jawab`
- Lampiran: `lampiran_array` (atau string JSON `lampiran`)
- Riwayat: `history_notes` (array of `['versi','tanggal','catatan']`)
- Pengesahan: `people_array, people_approve, pembuat, pembuat_date,
  DHdanSH, DHdanSHApprove, dhsh_date, PJO, PJOApprove, pjo_date`
- Alur pengesahan: **Pembuat (Group Leader) → DH/SH → PJO**
- Format: **A4 portrait**, header `position: fixed` (muncul tiap halaman),
  `@page margin: 190px 1.2cm 1cm 1.2cm`.

### 3.3 SP — `template_sp.blade.php` (mirip SOP, approval 2 tahap)
Variabel sama seperti SOP KECUALI **tanpa PJO**. Alur: **Pembuat → DH/SH**.
Jabatan pembuat di halaman pengesahan tertulis "Document Control".
Tetap punya section: Tujuan, Ruang Lingkup, Referensi, Definisi, Aktivitas, Lampiran.

### 3.4 IK — `template_ik.blade.php` (paling sederhana)
Hanya section **AKTIVITAS DAN TANGGUNG JAWAB** + **HALAMAN PENGESAHAN**.
Dari `tb_ik_baru::generateIK_PDF()`: `$doc->toArray()` + `people_array` + `aktivitas_array`.
Variabel: `no_dokumen, revisi, edisi, judul, efektif_date, dhsh_date,
aktifitas_tanggung_jawab, history_notes, people_array, pembuat, pembuat_date,
DHdanSH, DHdanSHApprove`.
> Catatan: `<title>` di file ini tertulis "SP" (sisa copy-paste) — tidak berpengaruh, boleh dirapikan.

---

## 4. Konvensi Marker Teks (WAJIB sama antara form & template)
Field teks disimpan sebagai string ber-marker, lalu di-parse di template:

- **Aktivitas & Tanggung Jawab** (`aktifitas_tanggung_jawab`):
  - `[END]` memisahkan antar item aktivitas
  - `[PIC]` memisahkan isi aktivitas dari nama PIC
  - `**teks**` dirender jadi `<strong>teks</strong>`
  - Penomoran otomatis: SOP/SP pakai `5.x`, IK pakai `1.x`
- **Tujuan / Ruang Lingkup / Referensi / Definisi**: dipisah **baris baru (`\n`)**,
  otomatis dinomori `1.x / 2.x / 3.x / 4.x`.
- **Catatan Revisi** (`history_notes[].catatan`): marker opsional
  `[HAL:halaman]`, `[END]`, `[TGL:tanggal]`.
- **Lampiran**: JSON array `[{ "judul": "...", "text": "...", "file": "path/relatif/di/storage" }]`.

---

## 5. Aturan Emas DomPDF (penyebab umum "format tidak pas")
1. **Gambar harus base64**, bukan `<img src="/path">`. DomPDF sering gagal load via URL.
   Pola: `file_get_contents(public_path(...))` → `base64_encode` → `data:image/...;base64,...`.
2. **Orientasi kertas**: JSA = `landscape`, sisanya = `portrait`. Set via
   `->setPaper('A4','portrait')` DAN/ATAU `@page { size: ... }` di CSS.
3. **`table-layout: fixed` + lebar kolom eksplisit** yang totalnya 100%. Wajib untuk tabel presisi.
4. **Header berulang tiap halaman**: `header { position: fixed; top: -170px }` +
   `@page { margin-top: 190px }` (lihat SOP/SP/IK).
5. **Unicode / centang**: gunakan `font-family: DejaVu Sans` untuk `✔`, `✓`, dsb.
6. **`page-break-inside: avoid`** pada `tr`/`.lampiran-wrapper` agar tidak terpotong.
7. Jika lampiran besar bikin memory error: `ini_set('memory_limit','512M')` (lihat IK).

---

## 6. Checklist Verifikasi di smartpro
- [ ] `composer require barryvdh/laravel-dompdf` terpasang
- [ ] 4 template Blade tersalin ke `resources/views/admin/Menus/Template/`
- [ ] `LogoPPA.png` & `Approved.png` ada di `public/assets/img/`
- [ ] Folder `*_pending` ada di `storage/app/public/` + `php artisan storage:link`
- [ ] Nama field DB / variabel di controller smartpro SAMA dengan kontrak Bagian 3
- [ ] Form menghasilkan marker `[END]`/`[PIC]`/`**bold**` sesuai Bagian 4
- [ ] Test render tiap jenis: JSA landscape, SOP/SP/IK portrait
