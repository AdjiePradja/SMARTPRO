# PRD — SmartPro Document Generator System
### PT. Putra Perkasa Abadi (PPA) — site PT. Adaro Indonesia

> **Versi:** v1.0 (Final untuk memulai development) · **Sifat:** Living Document
> **Penyusun:** [Nama kamu] — Magang Divisi ICTMD
> **Disusun sebagai:** Senior Software Analyst / AI Engineer / Ahli dokumentasi mutu pertambangan
> **Tanggal:** 12 Juli 2026
> **Dev environment:** Laravel 12 + Bootstrap 5 + MySQL di XAMPP · Dev tool: Claude Code

---

## Cara membaca dokumen ini
Ini living document — boleh berevolusi selama development. Setiap keputusan yang sudah dikunci ditandai **[LOCKED]**. Hal yang belum bisa difinalkan karena menunggu data ditandai 🟡 **PENDING-DATA**. Perubahan besar dicatat di Changelog (Bagian 16).

---

## 1. Ringkasan Eksekutif

### 1.1 Masalah
Tujuh departemen di PPA site Adaro membuat dokumen mutu (SOP, JSA, IK, SP) dengan format Word yang berbeda-beda antar departemen. Akibatnya: format tidak konsisten, penomoran manual, status persetujuan tak terlacak, review bolak-balik lewat file/email, tanpa kendali versi maupun jejak audit.

### 1.2 Solusi
Aplikasi web internal **SmartPro** yang menyediakan pengisian dokumen terpandu (tanpa membuka Word), menjaga format & penomoran otomatis yang konsisten, mengelola alur Pembuatan → Review → Revisi → Approval dengan jejak audit lengkap, menghasilkan PDF terformat, dan dibantu AI pada tahap review.

### 1.3 Prinsip desain **[LOCKED]**
> **Modular & maintainable, bukan skala raksasa.** User internal terbatas. Yang dikejar: menambah jenis dokumen dengan usaha minimal + **keselarasan format yang terjamin**. Menghindari over-engineering adalah bagian dari scope. Harus berjalan sempurna di XAMPP dulu, sebelum di-hosting di server lokal PPA.

### 1.4 Tujuh Departemen **[LOCKED]**
`SHE`, `PLANT`, `HCGA`, `FWA` (Finance Warehouse Accounting / Logistik — alias "FALOG", satu entitas), `ICTMD`, `PRODUKSI`, `ENGINEERING`.

---

## 2. Keputusan Arsitektural yang Sudah Dikunci

| # | Keputusan | Status |
|---|---|---|
| D1 | **Schema-Driven penuh (Opsi 3).** Satu definisi schema per jenis dokumen jadi sumber tunggal → menghasilkan form pengisian, preview, dan PDF cetak yang **selalu selaras**. | **[LOCKED]** |
| D2 | Format dokumen **sama antar 7 departemen** untuk jenis dokumen yang sama. Yang berbeda hanya **isi yang diketik user**, bukan struktur section. | **[LOCKED]** |
| D3 | Kop, footer, halaman pengesahan = **komponen bersama (partial)** yang di-inject ke semua dokumen → konsistensi kop/pengesahan mutlak. | **[LOCKED]** |
| D4 | **JSA dibangun terakhir** (tabel matriks kompleks). SOP dibangun pertama sebagai pola acuan, lalu IK & SP, baru JSA. | **[LOCKED]** |
| D5 | Reject **tidak menghapus isian** user. Status berubah ke `needs_revision`, isi dipertahankan, user hanya memperbaiki item yang ditandai. Berlaku bahkan untuk typo. | **[LOCKED]** |
| D6 | Preview **1:1 dengan format cetak**, muncul saat submit per-step, tampil di panel kanan, scrollable, bisa full-screen. | **[LOCKED]** |
| D7 | Autosave **wajib**. | **[LOCKED]** |
| D8 | Anotasi/komentar reviewer **per-item** (tiap item bisa punya feedback berbeda). | **[LOCKED]** |
| D9 | **Revisi setelah publish** (Revisi 0→1→2…) masuk MVP penuh (data + UI). Isi versi lama dipertahankan sebagai arsip. | **[LOCKED]** |
| D10 | AI Review: reviewer bebas **mengadopsi, meng-custom, atau menolak** saran AI. AI = alat bantu, keputusan tetap di reviewer. Provider AI dapat diganti (abstraksi). API disediakan user. | **[LOCKED]** |
| D11 | **Admin IT = wewenang penuh** atas semua (buat, review, approve, reject, hapus, ubah status, lihat 7 dept, kelola akun). **Seluruh aksi admin tercatat di audit log** (mitigasi wajib). | **[LOCKED]** |
| D12 | Visibilitas dokumen **strict per-departemen**. Hanya Section Head, Pimpinan (di lingkupnya), dan Admin IT (semua) yang menembus batas departemen. | **[LOCKED]** |
| D13 | Notifikasi **in-app saja** untuk MVP (tanpa email). | **[LOCKED]** |
| D14 | Konvensi kode: mengikuti **standar Laravel/PSR** (lihat catatan 10.2). | **[LOCKED]** |

### 2.1 Catatan risiko yang dicatat sadar (bukan penghalang, tapi wajib diketahui)
- **D11 melanggar prinsip segregation of duties** (ISO 27001 Annex A & SMKP, yang direferensikan SOP internal 3.7). Untuk konteks magang & tim ICT tepercaya, ini pilihan sadar. Mitigasi: **audit log wajib** mencatat setiap aksi admin, agar sistem tetap defensibel saat audit eksternal.
- **D1 (schema penuh termasuk JSA)** menantang di JSA. Mitigasi: D4 (JSA dikerjakan terakhir setelah pola terbukti).

---

## 3. Konsep Inti: Schema-Driven Documents

### 3.1 Prinsip
Setiap **jenis dokumen** didefinisikan oleh **schema** (JSON, disimpan di DB). Schema mendeskripsikan section, urutan (= tahap/step pengisian), field, dan tipe. **Satu engine** membaca schema → merender (a) form pengisian, (b) preview, (c) PDF. Karena ketiganya bersumber dari schema yang sama, **ketidakselarasan format secara struktural mustahil terjadi** — inilah cara paling menjamin format konsisten (prioritas utama proyek).

> Penting: schema **bukan** sistem menebak tata letak. Pengembang menuliskan tata letak secara eksplisit di schema. Engine hanya menggambar apa yang tertulis. Tidak ada keacakan.

### 3.2 Tipe field yang didukung
| Tipe | Contoh | Form | PDF |
|---|---|---|---|
| `text` | No dokumen, judul | input | teks |
| `textarea` | Deskripsi aktivitas | textarea | paragraf |
| `rich_list` | Poin Tujuan (auto 1.1, 1.2…) | list editor + add/remove | daftar bernomor |
| `table` | Matriks JSA | grid editable + add/remove row + merge | tabel |
| `repeatable_group` | Aktivitas (sub-judul+deskripsi+PIC) | grup berulang | blok berulang |
| `image` | Lampiran, foto langkah IK | upload JPG/PNG maks 2MB | gambar |
| `radio` / `checkbox` | Check (✓) JSA | radio/checkbox | simbol |
| `signature` | Halaman pengesahan | dropdown penandatangan | slot TTD/stempel |
| `reference_picker` | Referensi ISO/regulasi | multi-select + add | daftar |
| `auto` | No dokumen, tanggal | read-only (di-generate) + toggle manual | teks |

### 3.3 Prioritas pembangunan schema **[LOCKED per D4]**
1. **SOP** — schema lengkap sekarang (format tersedia dari dokumen yang diupload).
2. **IK & SP** — schema disusun saat contoh dokumen tersedia. 🟡 **PENDING-DATA**
3. **JSA** — terakhir (tabel matriks kompleks). 🟡 **PENDING-DATA**

### 3.4 Schema SOP (acuan konkret, disederhanakan)
```json
{
  "doc_type": "SOP",
  "header": "ppa_standard_header",
  "footer": "ppa_standard_footer",
  "approval_page": "ppa_pengesahan",
  "steps": [
    { "step": 1, "sections": [
      { "key": "tujuan", "label": "I. TUJUAN", "type": "rich_list", "auto_number": "1." },
      { "key": "ruang_lingkup", "label": "II. RUANG LINGKUP", "type": "rich_list", "auto_number": "2." }
    ]},
    { "step": 2, "sections": [
      { "key": "referensi", "label": "III. REFERENSI", "type": "reference_picker" },
      { "key": "definisi", "label": "IV. DEFINISI", "type": "rich_list", "auto_number": "4." }
    ]},
    { "step": 3, "sections": [
      { "key": "aktivitas", "label": "V. AKTIVITAS DAN TANGGUNG JAWAB",
        "type": "repeatable_group",
        "fields": [
          { "key": "sub_judul", "type": "text", "placeholder": "Sub Judul" },
          { "key": "deskripsi", "type": "textarea" },
          { "key": "pic", "type": "text", "placeholder": "PIC (mis. Tim ICT)" }
        ]}
    ]},
    { "step": 4, "sections": [
      { "key": "lampiran", "label": "VI. LAMPIRAN",
        "type": "repeatable_group",
        "fields": [
          { "key": "judul", "type": "text" },
          { "key": "isi", "type": "text_or_image" }
        ]}
    ]},
    { "step": 5, "sections": [
      { "key": "pengesahan", "type": "signature",
        "roles": ["dibuat_oleh", "ditinjau_oleh", "disetujui_oleh"] }
    ]}
  ]
}
```

---

## 4. Peran & Hak Akses (RBAC) **[LOCKED]**

| Peran | Cara masuk | Wewenang inti |
|---|---|---|
| **Admin IT** | Akun awal (seed) | **Penuh atas segalanya** (D11): kelola akun, buat/review/approve/reject/hapus/ubah status dokumen, lihat 7 dept. Semua aksi tercatat audit log. |
| **Pimpinan (PJO)** | Didaftar Admin IT (nama, NRP, jabatan) | **Approval final** (satu-satunya pemberi approve final). Lihat dokumen di lingkupnya lintas dept. Dashboard eksekutif. |
| **Section Head** | Didaftar Admin IT | Reviewer (peninjau). Lihat dokumen lintas dept di lingkupnya. |
| **Group Leader** | Didaftar Admin IT | Bisa jadi **pembuat** & **reviewer** (peninjau). Approve pendaftaran user dept. Lihat dokumen departemennya. |
| **User Departemen** | Daftar mandiri → di-approve GL/Pimpinan/Admin IT | Buat & isi dokumen, submit, revisi, lihat status dokumennya sendiri. Strict per-dept. |

**Alur peran pada dokumen (dari halaman pengesahan SOP):**
- **Dibuat Oleh** = User Dept **atau** Group Leader.
- **Ditinjau Oleh** (review) = Group Leader &/atau Section Head — dipilih via **dropdown** saat pengajuan.
- **Disetujui Oleh** (approve) = **hanya Pimpinan** — dipilih via **dropdown**.

> Gunakan `spatie/laravel-permission` untuk RBAC. Jangan bangun sistem role manual.

---

## 5. Document Lifecycle (Alur Utama) **[LOCKED]**

```
[User Dept / GL] Buat & isi dokumen (multi-step wizard, autosave, preview per-step)
      │ submit  (pilih peninjau & approver via dropdown)
      ▼
[Status: SUBMITTED → IN_REVIEW]
      │
[Reviewer: GL / Section Head] Review per-item + AI assist (opsional)
      ├── semua sesuai ──► [PENDING_APPROVAL] ──► ke Pimpinan
      └── ada temuan   ──► anotasi per-item ──► [NEEDS_REVISION] (isi dipertahankan)
                                                   │
                    [User Dept] Revisi terarah (lihat highlight) → submit ulang
                                                   │ revision_round++
                                                   ▼ kembali ke Reviewer
[Pimpinan] Approval final
      ├── setuju ──► [PUBLISHED] ──► kategorisasi (terkendali / tidak terkendali)
      └── tolak  ──► review + komentar ──► [NEEDS_REVISION] ──► ke User Dept
```

### 5.1 State machine
`draft` → `submitted` → `in_review` → (`needs_revision` ⇄ `in_review`) → `pending_approval` → (`published` | `needs_revision`) · plus `archived`, `obsolete` (versi lama saat direvisi setelah publish).

### 5.2 Dua tipe revisi (keduanya MVP)
- **Tipe 1 — akibat reject** (pra-publish): isi dipertahankan, user perbaiki item bertanda, `revision_round++`.
- **Tipe 2 — setelah publish** (D9): dokumen published perlu diperbarui → jadi Revisi berikutnya. Versi lama → `obsolete` (diarsip, tak dihapus), versi baru mewarisi nomor dokumen sama dengan `No. Revisi` naik. Diakses via menu **Dokumen Revisi**.

---

## 6. Kategorisasi Dokumen **[LOCKED]**

### 6.1 Kelas dokumen
- **Dokumen Inti:** SOP, JSA, IK, SP — dimiliki semua 7 departemen, format seragam.
- **Dokumen Independen per Dept:** mis. HIRADC (SHE), Form Inspeksi (ICTMD), dll — 🟡 menyusul.
- **Dokumen Lintas Departemen:** berita acara, form peminjaman, notulen rapat, dll — bisa diakses semua dept — 🟡 menyusul.

### 6.2 Terkendali vs Tidak Terkendali (sebagai **notes/atribut**, sesuai keputusan)
- **Terkendali:** dokumen yang **tidak disebarluaskan** (salinan resmi terkontrol).
- **Tidak Terkendali:** dokumen yang **sudah disebarluaskan** / hasil cetak.
- Footer standar tetap: *"Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak."*

---

## 7. Generasi Output (PDF) **[LOCKED sebagian]**
- **Engine:** Blade (dari schema) → HTML terformat → PDF via `barryvdh/laravel-dompdf` (ringan, jalan di XAMPP). Naik ke `spatie/laravel-pdf` (Browsershot) bila presisi tabel JSA menuntut.
- **Elemen konsisten (partial bersama, D3):** kop (logo PPA, jenis dokumen, No. Dokumen, No. Revisi, Edisi, Tgl Terbit, Tgl Revisi) · footer · halaman pengesahan (Nama/Jabatan/Tanggal/Pengesahan + stempel APPROVED saat published).
- **Word export:** ditunda (fitur fase belakang).

### 7.1 Penomoran otomatis **[LOCKED]**
Format: `PPA-ADRO-{JENIS}-{DEPT}-{NN}` (mis. `PPA-ADRO-SOP-ICTMD-01`). Di-generate dari: prefix perusahaan + site + jenis + kode dept + sequence increment per (jenis+dept). Toggle **Input Manual** tersedia (dengan validasi keunikan).

---

## 8. Fitur AI (Review Assist) **[LOCKED]**
- **Scope:** summarize isi dokumen · highlight bagian tidak sesuai/tidak optimal · saran perbaikan.
- **Alur adopsi:** AI menghasilkan saran terstruktur (JSON: `{item_key, severity, issue, suggestion}`) → reviewer bisa **adopsi apa adanya**, **edit/custom**, atau **tolak**. Saran yang diadopsi/di-custom diteruskan menjadi **anotasi per-item** ke user dept.
- **Abstraksi provider:** interface `AiReviewerInterface` + implementasi awal (API disediakan user). Ganti provider = ganti binding, bukan rewrite.
- **Prinsip:** AI tidak pernah approve/reject sendiri. Keputusan tetap reviewer (akuntabilitas dokumen tambang).
- 🟡 **Catatan kepatuhan:** pengiriman isi dokumen ke API AI eksternal menyentuh ISO 27001. Konfirmasikan ke IT/atasan bahwa ini diizinkan; sediakan opsi menonaktifkan AI bila perlu.

---

## 9. UX/UI **[LOCKED prinsip]**

### 9.1 Kenapa user betah (mengalahkan Word)
Autosave (tak pernah kehilangan progres) · wizard multi-step + progress bar (dokumen panjang terasa ringan) · preview 1:1 terformat (user cuma mikir isi) · penomoran & format otomatis · status tracking transparan · revisi terarah (klik highlight → lompat ke item) · field pre-filled (referensi yang selalu sama) · notifikasi in-app · validasi inline.

### 9.2 Guideline visual
Bootstrap 5, modern, animasi minimalis (transisi halus, hover, skeleton loading), empty states & loading states dirancang, aksesibilitas dasar (kontras, label jelas, keyboard-navigable). Alpine.js disarankan untuk interaktivitas wizard/preview tanpa kompleksitas SPA.

### 9.3 Preview
MVP: preview muncul saat submit tiap step, panel kanan, scrollable, full-screen-able. (Live-preview-keystroke = fase belakang.)

---

## 10. Arsitektur & Tech Stack **[LOCKED]**

### 10.1 Stack
Laravel 12 (PHP 8.2+) · Blade + Bootstrap 5 + Alpine.js · MySQL 8 · DomPDF (→ Browsershot bila perlu) · AI via HTTP client di balik interface · Auth: Laravel Breeze/Fortify + `spatie/laravel-permission` · Dev: XAMPP + `php artisan serve` · Dev tool: Claude Code.

### 10.2 Konvensi kode **[LOCKED — standar Laravel]**
Ikuti standar Laravel/PSR: `KaryawanController`, model `Karyawan` (singular), Blade `karyawan/index.blade.php`. Logika di **Service classes** (bukan menumpuk di controller). Ini memaksimalkan kompatibilitas dengan tooling & Claude Code.
> Catatan: konvensi prefix `C.../M...` yang sempat dipertimbangkan **tidak dipakai** karena mengganggu auto-discovery & route model binding Laravel.

### 10.3 Struktur modular per domain
`Auth` · `UserManagement` · `MasterData` · `Document` (schema, form-engine, render) · `Review` · `Approval` · `Ai` · `Dashboard` · `Notification` · `Audit`.

---

## 11. Model Data (ERD Konseptual)
Tabel inti (berevolusi):
- **users** (id, nrp, name, jabatan, email, password, department_id, status[pending/active/rejected], …)
- **roles / permissions / role_user** (spatie)
- **departments** (id, code, name, alias)
- **document_types** (id, code[SOP/JSA/IK/SP/…], name, schema_json, approval_flow_id, class[inti/independen/lintas], scope)
- **approval_flows** + **approval_flow_steps** (order, role, label[Dibuat/Ditinjau/Disetujui])
- **documents** (id, doc_number, document_type_id, department_id, title, status, current_revision_round, no_revisi, edisi, is_controlled[bool], created_by, published_at)
- **document_contents** (id, document_id, section_key, value_json) ← isi per section, fleksibel mengikuti schema (tak perlu kolom tetap per jenis dokumen)
- **document_versions** (id, document_id, no_revisi, snapshot_json, created_at) ← histori revisi setelah publish
- **reviews** (id, document_id, reviewer_id, revision_round, decision, summary)
- **review_annotations** (id, review_id, section_key, item_ref, severity, comment, ai_generated[bool], ai_adopted[bool])
- **approvals** (id, document_id, approver_id, decision, comment, signed_at)
- **notifications** (Laravel default)
- **audit_logs** (id, user_id, document_id, action, meta_json, created_at) ← **wajib**, termasuk aksi admin
- **attachments** (id, document_id, section_key, path, mime, size)

> Kunci fleksibilitas: isi disimpan sebagai `value_json` per section → menambah jenis dokumen tak butuh migrasi tabel baru. Soft delete diaktifkan (jejak terjaga).

---

## 12. Daftar Menu/Fitur MVP **[LOCKED]**

**Wajib MVP:**
- Login / Logout (+ keamanan bawaan Laravel: CSRF, hashing, rate-limit login)
- Registrasi user dept + approval akun
- Manajemen User (admin IT daftar GL/Section Head/Pimpinan; approve/tolak user dept)
- Dashboard (grafik & status, sesuai hak akses)
- Dokumen Baru (buat 4 dokumen inti; SOP lengkap dulu)
- Dokumen Revisi (revisi setelah publish)
- Review / Task Saya (antrian reviewer)
- Approval / Persetujuan Saya (antrian pimpinan)
- Status Dokumen Saya (user dept)
- Arsip / Dokumen Final (per departemen, difilter per jenis)
- **Filter** (basic, wajib)
- **Pencarian** dokumen (by nomor/judul/dept)
- **Notifikasi** in-app (basic, wajib)
- **Audit Log** (menu tersendiri; admin & pemantauan)
- Data Akun / Profil
- Visibilitas admin menyeluruh (7 dept)

**Master Data:** di-**seed** lewat kode untuk MVP (7 dept + jenis dokumen + aturan penomoran). Halaman edit master data = fase belakang.

**Ditunda (pasca-MVP):** export Word, OCR input existing, PDF→Word, live-preview-keystroke, halaman edit master data, dokumen independen & lintas-dept.

---

## 13. Rencana SDLC (Iterative/Incremental)

**Fase 0 — Discovery (sebagian selesai):** finalisasi PRD ini · kumpulkan contoh JSA/IK/SP (🟡 pending) · konfirmasi kepatuhan AI.

**Fase 1 — Fondasi:** setup Laravel/DB/auth/RBAC · seed master data · registrasi & approval user · admin buat akun GL/SH/Pimpinan.

**Fase 2 — Document Engine (paling berisiko, kerjakan awal):** schema loader + form-engine + preview-engine + wizard + autosave · implement **SOP end-to-end** sampai PDF · penomoran otomatis. **Target: 1 SOP jadi PDF utuh.**

**Fase 3 — Jenis dokumen lain:** IK, SP (saat contoh ada), **JSA terakhir**. Validasi engine benar-benar generik.

**Fase 4 — Review & Approval:** anotasi per-item · dua tipe revisi · approval pimpinan · kategorisasi terkendali · notifikasi · audit log.

**Fase 5 — AI Assist:** integrasi provider · summarize/highlight/saran · alur adopsi/custom/tolak.

**Fase 6 — Dashboard & Reporting:** grafik & statistik per hak akses · filter · pencarian.

**Fase 7 — Pasca-MVP:** dokumen independen & lintas-dept · export Word · OCR · dll.

> **Disiplin scope:** fitur Fase 7 tidak boleh masuk sebelum Fase 1–6 stabil. Bangun SOP dulu sampai sempurna sebelum menduplikasi ke dokumen lain.

---

## 14. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Scope creep | Tak selesai saat magang | MVP dikunci; Fase 7 = backlog |
| Schema engine terlalu ambisius di awal | Lama di fondasi | Mulai SOP; buktikan sebelum generalisasi; JSA terakhir |
| JSA layout kompleks di PDF | Output jelek | Cadangan Browsershot |
| Kepatuhan data ke AI cloud | Isu ISO 27001 | Konfirmasi izin; mode tanpa AI |
| Admin wewenang penuh (D11) | Segregation of duties lemah | Audit log wajib atas semua aksi admin |
| Kehilangan isian | User kapok | Autosave + versioning sejak Fase 2 |
| Form/preview/cetak tak selaras | Format salah | Schema-driven (satu sumber) menutup risiko ini |

---

## 15. Data yang masih dibutuhkan (🟡 PENDING-DATA)
1. **Blank template + 1 contoh terisi** untuk **JSA, IK, SP** (SOP sudah ada). Diperlukan untuk menyusun schema & blade cetaknya. Dapat ditambahkan bertahap saat development di Claude Code.
2. Konfirmasi apakah rantai approval (Dibuat→Ditinjau→Disetujui) **seragam** untuk semua jenis dokumen, atau ada variasi.
3. Konfirmasi izin kepatuhan pengiriman isi dokumen ke API AI eksternal.
4. Daftar konkret **dokumen independen per departemen** & **lintas departemen** (untuk Fase 7).

---

## 16. Changelog
- **v1.0 (12 Jul 2026):** Final untuk memulai development. Mengunci 14 keputusan arsitektural (D1–D14), schema-driven penuh, RBAC 5 peran, dua tipe revisi, admin wewenang penuh + audit wajib, menu MVP, dan rencana SDLC 7 fase. SOP sebagai dokumen acuan pertama; JSA/IK/SP menunggu contoh.
