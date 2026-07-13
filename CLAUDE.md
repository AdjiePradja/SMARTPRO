# CLAUDE.md — Aturan Proyek SmartPro Document Generator (PT PPA) — v2

> Dibaca otomatis Claude Code tiap sesi. Patuhi semua. Referensi lengkap: `docs/PRD-SmartPro-v2.0.md`.
> Jika file ini dan PRD berbeda soal teknis, file ini menang. PRD menang untuk keputusan produk.

## 1. Konteks
Web internal PT Putra Perkasa Abadi (site Adaro) untuk menggenerate dokumen mutu (SOP, SP, IK, JSA) bagi 7 departemen. Pengembang mahasiswa magang yang sedang belajar; kode akan direview senior IT kantor. **Utamakan kode jelas, standar, terdokumentasi** di atas keringkasan/kepintaran. Development SUDAH berjalan (sekitar Fase 2) — pekerjaan sekarang adalah MENYELARASKAN, bukan mulai dari nol.

## 2. Tech stack (WAJIB)
Laravel 12 (PHP 8.2+) · Blade + Bootstrap 5 + Alpine.js (JANGAN Vue/React/Inertia/Tailwind-SPA) · MySQL 8 via XAMPP · Auth: NRP+password · RBAC: spatie/laravel-permission · PDF: barryvdh/laravel-dompdf · Icon: Bootstrap Icons (JANGAN emoji).

## 3. Konvensi kode (WAJIB)
- Controller & Model: STANDAR Laravel (`DocumentController`, `Document`). DILARANG prefix C/M (Ckaryawan/Mkaryawan) — menghindari rename massal & menjaga route model binding.
- Blade seragam pola `{jenis}/{aksi}`: `resources/views/sop/index.blade.php`, `sop/create.blade.php`, `sop/edit.blade.php`.
- Route seragam: `sop.index`, `sop.create`, `sop.edit`, dst.
- Logika bisnis di Service class (`app/Services`). Validasi via Form Request. Otorisasi via Policy/Gate. Ikuti PSR-12.

## 4. Larangan KERAS (tanpa konfirmasi eksplisit saya)
- JANGAN `migrate:fresh`/`migrate:refresh` atau apa pun yang MENGHAPUS DATA. Tanya dulu.
- JANGAN commit `.env`. Pastikan `.env` di `.gitignore`.
- JANGAN hapus file/folder tanpa konfirmasi.
- JANGAN install package besar tanpa izin + alasan.
- JANGAN kerjakan banyak fitur sekaligus. Satu tugas per fase → berhenti → tunggu saya review.
- JANGAN mengarang struktur dokumen IK/JSA/SP atau dokumen independen — contohnya belum ada, TUNGGU dari saya.

## 5. Cara kerja
- Kerjakan satu fase/tugas dari `docs/INSTRUKSI-Merging-Berfase.md`, lalu BERHENTI, jelaskan singkat, tunggu saya review + commit Git.
- Keputusan yang belum jelas di PRD → TANYA, jangan berasumsi diam-diam.
- Perubahan menyentuh DB → tunjukkan migration, tunggu persetujuan.

## 6. Role, jabatan, alur (dari PRD v2)
- Jabatan: staff, group_leader, section_head, pimpinan. Role fungsional: Pembuat, Auditor/Peninjau, Approver. Admin IT = full akses (semua aksi tercatat audit log).
- Alur ditentukan JABATAN PEMBUAT:
  - Pembuat Staff -> Peninjau GL (dept sama) -> Approver Section Head ATAU Pimpinan (pembuat pilih di dropdown).
  - Pembuat GL -> Peninjau Section Head -> Approver Pimpinan.
- Section Head dual-role: peninjau (jika pembuat GL) / approver (jika pembuat Staff). Hanya lihat dept sendiri.
- Hanya Pimpinan lihat semua dept. Peninjau & approver dipilih pembuat via dropdown (tampil Nama+NRP+Dept).
- Tidak boleh satu orang meninjau+menyetujui dokumen yang sama.

## 7. Keputusan arsitektur terkunci
- Schema-driven: tiap jenis dokumen didefinisikan schema JSON (di DB), sumber tunggal untuk form/preview/PDF. Lihat `docs/schema-sop.json`.
- Kop/footer/pengesahan = Blade partial bersama (`_kop`, `_footer`, `_pengesahan`). Logo di `public/images/logo-ppa`.
- Isi dokumen disimpan value_json per section (tabel document_contents). JANGAN kolom tetap per jenis.
- Urutan bangun: SOP dulu sampai jadi PDF utuh -> IK -> SP -> JSA terakhir.
- Proses pengisian 2 STEP (bukan 5).
- Dua jenis revisi (JANGAN disatukan):
  - Tipe A (ditolak, belum terbit): mulai dari menu Dokumen Revisi pembuat. No revisi tetap 0.
  - Tipe B (pembaruan dokumen Berlaku, 0->1): mulai dari sub-menu Departemen -> jenis dokumen -> Action "Ajukan Revisi". Diajukan GL/Pimpinan/Admin. Versi lama disimpan (document_versions), jadi obsolete setelah versi baru approved.
- Status dokumen (TANPA persentase): draft, in_review, rejected, pending_approval, published/Berlaku, Sedang Direvisi, obsolete.
- Edit & Hapus hanya untuk status draft.
- Pembuat utama = yang menekan Buat Dokumen (satu-satunya TTD di pengesahan). Pembuat tambahan hanya di log.
- Reject oleh approver -> feedback wajib -> balik ke GL -> jadi rejected; peninjau yang meloloskan dinotifikasi.
- Lampiran foto disimpan per dept: storage/app/public/lampiran/{DEPT}/{JENIS}/.

## 8. Penomoran
`PPA-ADRO-{JENIS}-{DEPT}-{NN}` auto + toggle manual (validasi unik).

## 9. UX
Tema terang default + toggle dark. Nol emoji (Bootstrap Icons/vector). Badge status berwarna, breadcrumb, kolom Status+Action+Lihat PDF konsisten di semua tabel. Preview 1:1 di alur pembuatan. Modern, clean, interaktif.

## 10. Notifikasi lonceng per role
Pembuat: rejected/approved. Peninjau: ada yang perlu ditinjau; dokumen yang diloloskan ditolak approver. Approver: ada yang perlu disetujui. In-app saja.

## 11. Keamanan
CSRF, hashing, rate-limit login, validasi semua input, Policy/Gate per peran, `.env` tidak masuk Git.

## 12. Referensi (di folder docs/)
- PRD-SmartPro-v2.0.md (utama)
- INSTRUKSI-Merging-Berfase.md (urutan kerja)
- schema-sop.json (cetak biru SOP)
- Dokumen SOP contoh (PDF) — acuan format cetak. LIHAT tiap bikin/ubah template cetak.
- Screenshot form & review — acuan UX.
- IK/JSA/SP: contoh menyusul — jangan dikarang.
