# ROADMAP Eksekusi — Fase 1 & 2 (SmartPro)

> Kerjakan **satu tugas per satu waktu**. Selesaikan → review → commit Git → lanjut.
> Jangan lompat. Jangan kerjakan beberapa tugas sekaligus. Setelah tiap tugas, berhenti & jelaskan.

---

## Persiapan (lakukan SEBELUM tugas 1)
- [ ] `git init` di folder proyek (jika belum). Pastikan `.env` ada di `.gitignore`.
- [ ] Pastikan XAMPP jalan (Apache + MySQL). Buat database kosong via phpMyAdmin, mis. `smartpro`.
- [ ] Commit awal: "chore: initial Laravel project".

---

## FASE 1 — Fondasi

### Tugas 1.1 — Setup & koneksi database
- Pastikan Laravel 12 terpasang, konfigurasi `.env` ke MySQL XAMPP (db `smartpro`).
- Test koneksi (`php artisan migrate` dengan migration bawaan Laravel).
- **Berhenti. Konfirmasi koneksi berhasil.**

### Tugas 1.2 — Autentikasi
- Install Laravel Breeze (Blade + Bootstrap, bukan Tailwind kalau memungkinkan; kalau Breeze memaksa Tailwind, catat & diskusikan).
- Halaman login & logout jalan. Registrasi sementara aktif (akan dimodifikasi di 1.5).
- **Berhenti. Test login/logout.**

### Tugas 1.3 — RBAC (spatie/laravel-permission)
- Install `spatie/laravel-permission`.
- Buat 5 role: `admin_it`, `pimpinan`, `section_head`, `group_leader`, `user_dept`.
- Seeder role. Buat 1 akun admin IT awal via seeder.
- **Berhenti. Verifikasi role & akun admin ada di DB.**

### Tugas 1.4 — Master data: Departemen
- Migration + model `Department` (id, code, name, alias).
- Seeder 7 departemen: SHE, PLANT, HCGA, FWA (alias FALOG), ICTMD, PRODUKSI, ENGINEERING.
- **Berhenti. Verifikasi 7 dept ter-seed.**

### Tugas 1.5 — Registrasi user dept + approval akun
- Tambah kolom user: `nrp`, `jabatan`, `department_id`, `status` (pending/active/rejected).
- User dept daftar mandiri → status `pending`.
- Admin IT / GL / Pimpinan bisa approve/tolak (ubah status).
- User `pending` tidak bisa login penuh (arahkan ke halaman "menunggu persetujuan").
- **Berhenti. Test alur daftar → approve → login.**

### Tugas 1.6 — Manajemen user oleh Admin IT
- Halaman admin: daftar semua user, buat akun GL/Section Head/Pimpinan (isi nrp, nama, jabatan, dept, role).
- **Berhenti. Test buat akun tiap peran.**

### Tugas 1.7 — Layout dasar & navigasi
- Layout Bootstrap: sidebar (menu sesuai peran), navbar (notifikasi placeholder, profil).
- Menu tampil kondisional per role (Gate/Policy).
- **Berhenti. Cek tiap peran lihat menu yang sesuai.**

---

## FASE 2 — Document Engine (mulai dari SOP)

### Tugas 2.1 — Struktur database dokumen
- Migration + model: `document_types`, `documents`, `document_contents`, `document_versions`, `attachments`, `audit_logs`.
- Ikuti ERD di PRD Bagian 11. Aktifkan soft delete pada `documents`.
- Seed `document_types` dengan SOP (isi kolom `schema_json` dari `schema-sop.json`).
- **Berhenti. Review skema tabel sebelum migrate. JANGAN migrate:fresh.**

### Tugas 2.2 — Schema loader
- Service yang membaca `schema_json` dari `document_types` dan mem-parse-nya jadi struktur yang bisa dipakai renderer.
- **Berhenti. Test load schema SOP.**

### Tugas 2.3 — Form renderer (Langkah 1 dokumen: header)
- Halaman "Dokumen Baru - Langkah 1": no dokumen auto-generate (+ toggle manual), judul, jenis (SOP), departemen (auto dari user), pembuat (auto).
- Penomoran otomatis `PPA-ADRO-SOP-{DEPT}-{NN}`.
- **Berhenti. Test generate nomor & lanjut ke langkah 2.**

### Tugas 2.4 — Form renderer generik (multi-step body)
- Renderer yang menggambar field dari schema per step (rich_list, repeatable_group, reference_picker, text_or_image, user_picker).
- Wizard multi-step dengan tombol Kembali/Next.
- Fungsi "tambah/hapus" untuk rich_list & repeatable_group.
- **Berhenti. Test isi tiap tipe field.**

### Tugas 2.5 — Autosave
- Autosave draft ke `document_contents` (value_json per section) secara berkala / saat pindah step.
- Draft recovery saat user buka lagi.
- **Berhenti. Test: isi sebagian, refresh, data kembali.**

### Tugas 2.6 — Preview per-step
- Saat submit sebuah step, tampilkan preview 1:1 (format cetak) di panel kanan, scrollable + tombol full-screen.
- Preview memakai partial `_kop`, `_footer`, `_pengesahan`.
- **Berhenti. Test preview cocok dengan format SOP.**

### Tugas 2.7 — Generate PDF
- Blade cetak SOP + partial bersama → DomPDF.
- Tombol download/preview PDF.
- **Berhenti. Bandingkan PDF dengan dokumen SOP contoh. Harus mirip format.**

---

## Setelah Fase 2 selesai
Satu SOP bisa dibuat dari nol sampai jadi PDF terformat. Baru lanjut:
- Fase 3: duplikasi pola ke IK, SP (saat contoh tersedia), JSA terakhir.
- Fase 4: review & approval + audit log + notifikasi.
- Fase 5: AI assist.
- Fase 6: dashboard, filter, pencarian.

**Aturan emas:** jangan sentuh Fase 3+ sebelum satu SOP benar-benar jadi PDF utuh dan stabil.
