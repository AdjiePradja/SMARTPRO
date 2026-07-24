# Rencana: Renewal Data + Halaman/Edisi/Revisi + Lembar Revisi + Perbaikan Tampilan

## Context

Batch besar dari pemilik produk, dikerjakan **fase demi fase** (CLAUDE.md §5: satu fase → berhenti → review → commit). Tiga kelompok:

1. **Renewal** — kosongkan data operasional (dokumen, log aktivitas, sebagian user) agar web "dipakai dari awal", TANPA menghapus konfigurasi (roles, permission, departemen, jenis dokumen).
2. **Fitur dokumen** — penomoran halaman "X dari Y" di kop; kolom **Edisi** pada JSA; kop atas JSA berulang tiap halaman (blok info+TTD tetap sekali); logika **Edisi/Revisi** naik saat approve dengan roll-over; **Lembar Revisi** (halaman CATATAN REVISI di depan dokumen) + **form log revisi** sebagai tahap ke-3 wizard yang hanya muncul saat proses revisi; dokumen sebelum-revisi jadi Tidak Berlaku otomatis.
3. **Tampilan** — menu GL dirapikan; field opsi (+/−) disejajarkan & diberi jarak.

Referensi: `docs/JSA new.docx` (kop + Edisi + "Halaman 2 dari 2" berulang di hal. 2), `docs/Lembar Revisi.docx` (tabel CATATAN REVISI), `docs/lembar revisi/langkah 1|2|2 lanjutan.png` (form log revisi).

### Keputusan pemilik (dikonfirmasi)
- **User disimpan saat renewal:** Admin + 1 akun contoh tiap peran (GL/SH/DH/PJO/Non-Staff).
- **Roll-over revisi:** 0,1,2,3,4 → revisi berikutnya jadi **Edisi+1, Revisi 0** (angka 5 tak pernah tampil).
- **Tombol manual "Jadikan Tidak Berlaku":** TETAP ada (di samping mekanisme otomatis dari revisi).
- **Form log revisi (tahap 3):** menyesuaikan `docs/Lembar Revisi.docx` agar menghasilkan lembar CATATAN REVISI — Edisi/Revisi (auto, bisa edit) + Judul (dari dokumen) + baris **Catatan Perubahan per halaman** (tanggal / halaman / catatan). Isi dokumen tetap diedit di tahap 2.

---

## Temuan penting dari eksplorasi (dasar rencana)

- **Kop JSA `docs/JSA new.docx`:** blok atas = logo | FORMULIR JOB SAFETY ANALYSIS | No.Dokumen | Revisi | Tgl Efektif | **Halaman "X dari Y"** | **Edisi**. Di **halaman 2 blok atas ini BERULANG** lalu langsung header tabel analisa. Blok info+TTD (No.Pekerjaan/JSA, Dibuat/Direview/Disetujui, dept, APD, tools, TTD) **hanya di halaman 1**. → membalik sebagian `render-jsa` (kop atas kembali berulang; blok info tetap sekali).
- **Lembar Revisi (`Lembar Revisi.docx`)** = judul "CATATAN REVISI" + tabel kolom **NO | NO. REV | TANGGAL REV | HAL. | CATATAN REVISI**, baris terkumpul lintas revisi.
- Kolom `edisi` (string) & `no_revisi` (int) **sudah ada** di tabel documents. Tak perlu migrasi untuk itu.
- Roll-over sekarang: `no_revisi` di-*bump* saat `requestRevision` (pembuatan). Approve → published + versi lama sedang_direvisi → obsolete (ApprovalController.store, sudah ada).
- Penomoran dokumen **diturunkan dari data** (`DocumentNumberService`), jadi menghapus semua dokumen otomatis mereset nomor ke 01 — tak ada counter terpisah.
- SH/DH/PJO **sudah** punya `document.request_revision` → poin "SH/DH bisa ajukan revisi" praktis sudah terpenuhi (tombol di `berlaku.blade` di-gate `@can('document.request_revision')`). Cukup diverifikasi.
- Wizard membaca `$schema->stepCount()`; tombol Simpan/Kirim tampil di langkah terakhir (`edit.blade`).
- DomPDF barryvdh dipakai; `page_text()` mendukung placeholder `{PAGE_NUM}`/`{PAGE_COUNT}` (dipanggil dari controller sesudah `render()`), **tanpa** perlu `enable_php`.

---

## Fase 0 — Renewal (kosongkan data, pertahankan konfigurasi)

Buat **artisan command** `app/Console/Commands/RenewData.php` (`php artisan smartpro:renew`), ber-guard konfirmasi (`--force` untuk lewati prompt). Isi:
- `forceDelete` seluruh: `documents`, `document_contents`, `document_versions`, `document_authors`, `reviews`, `approvals`, `attachments` (+ hapus berkas `storage/app/public/lampiran/**`), `notifications`, `audit_logs`.
- Hapus user **kecuali**: ADM-0001 + satu per peran → PJO-0001, SH-0001, GL-0001, satu Non-Staff (STF-0001). Karena belum ada akun `departemen_head`, sisakan/late-assign satu (mis. konversi SHE-5555 → departemen_head, atau catat agar didaftarkan). Akan dikonfirmasi singkat saat eksekusi.
- **TIDAK** menyentuh: roles, permissions, departments, document_types, settings, .env.
- Penomoran otomatis reset (turun dari data).

Alasan pakai command (bukan `migrate:fresh`): CLAUDE.md §4 melarang operasi penghapus-skema; command bertarget lebih aman, terulang, dan auditable.

**Verifikasi:** `php artisan smartpro:renew --force` lalu cek `Document::count()==0`, `AuditLog::count()==0`, `User::count()==6`; buat 1 draft SOP → nomornya `-01`.

---

## Fase 1 — Penomoran halaman + Edisi JSA + kop atas JSA berulang

- **`resources/views/documents/print/render-jsa.blade.php`:** kembalikan **kop atas** ke `thead` `page-frame` (berulang tiap halaman). Blok **info+TTD** jadi baris `tbody` pertama (otomatis sekali). Tabel analisa tetap punya `thead` sendiri (header kolom berulang). Footer tetap mengalir sekali di akhir.
- **`resources/views/documents/print/_kop_jsa.blade.php`:** tambah sel **Halaman** (nilai dikosongkan — diisi via `page_text`) dan **Edisi** (`$document->edisi ?? 1`). No.Dokumen tetap auto (`displayNumber()`), Revisi = `no_revisi`, Tgl Efektif = `form_tgl_efektif`.
- **`resources/views/documents/print/_kop.blade.php` (SOP/IK/SP):** tambah baris meta **Halaman** (nilai dikosongkan untuk di-stamp).
- **Stamp "X dari Y"** di `DocumentController::pdf()`: sesudah `Pdf::loadView()->getDomPDF()->render()`, panggil `$canvas->page_text($x, $y, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 8, [0,0,0])` pada koordinat sel Halaman (beda x untuk portrait vs landscape), lalu `stream()`. Preview HTML tampilkan placeholder "Halaman 1 dari 1" (aliran HTML tak bisa hitung per-halaman) — dapat catatan kecil.
- **Test:** perbarui `tests/Feature/PrintLayoutTest.php` — kop atas JSA kini **berulang = jumlah halaman**; blok "No. Pekerjaan/JSA" tetap **1×**; PDF memuat "dari" (page_text). Tambah assert Edisi tampil.

---

## Fase 2 — Logika Edisi/Revisi (roll-over) saat approve

- Helper roll-over (di `DocumentService`): `nextEditionRevision(int $edisi, int $rev): array` → `rev+1`; bila `rev+1 >= 5` ⇒ `[$edisi+1, 0]`, selain itu `[$edisi, $rev+1]`.
- **`DocumentService::requestRevision()`:** ganti `no_revisi = old+1` menjadi hasil `nextEditionRevision(old->edisi, old->no_revisi)` (mengisi edisi & no_revisi versi baru). Set `revises_document_id` (Fase 3).
- Nilai Edisi/Revisi ini **pra-isi** di form log revisi (tahap 3) dan **bisa diedit manual**. Menjadi resmi (live) saat dokumen di-approve (ApprovalController sudah men-*publish* + meng-*obsolete* versi lama — tak berubah).
- **Test:** `nextEditionRevision` unit-style via revisi ke-5 (rev 4 → Edisi 2 Rev 0) di `RevisionTypeBTest`/test baru.

---

## Fase 3 — Wizard tahap-3 (form log revisi) + halaman CATATAN REVISI + auto Tidak Berlaku

- **Migrasi baru** `add_revises_document_id_to_documents`: kolom `revises_document_id` nullable FK → menandai draft revisi (dipakai untuk memunculkan tahap-3 & halaman CATATAN REVISI). *(DB berubah — ditunjukkan & disetujui lewat persetujuan rencana ini.)*
- **Wizard dinamis** (`resources/views/documents/edit.blade.php` + `DocumentController::saveStep`): bila `revises_document_id` terisi, jumlah langkah efektif = `stepCount()+1`.
  - Langkah 2 (isi dokumen) saat revisi: tombol jadi **Kembali / Preview / Langkah Berikutnya** saja (Simpan & Kirim dipindah ke tahap 3).
  - Langkah 3 = **form log revisi** (partial `documents/fields/_revision_log.blade.php`): Edisi & Revisi (auto, editable) + Judul (readonly, dari dokumen) + baris **Catatan Perubahan per halaman** (tanggal / halaman / catatan) dengan tombol "+ Tambah Catatan Per Halaman" (pola Alpine `richList`/`repeatable`). Tombol **Simpan** & **Kirim** ada di sini.
  - Simpan ke content section `catatan_revisi` (array baris) + kolom `edisi`/`no_revisi`. `requestRevision` menyalin content lama → baris CATATAN REVISI **terkumpul** lintas revisi otomatis.
- **Halaman CATATAN REVISI** (`documents/print/_catatan_revisi.blade.php`), disisipkan **paling depan** di `render.blade.php` & `render-jsa.blade.php`, **hanya** bila dokumen punya baris `catatan_revisi` (mis. hasil revisi). Format `Lembar Revisi.docx`: tabel **NO | NO. REV | TANGGAL REV | HAL. | CATATAN REVISI**.
- **Auto Tidak Berlaku:** alur sudah ada (requestRevision → versi lama `sedang_direvisi`; approve versi baru → versi lama `obsolete`). Dipertahankan & diverifikasi. Tombol manual "Jadikan Tidak Berlaku" **tetap** (keputusan pemilik).
- **Test:** revisi end-to-end → tahap 3 muncul, `catatan_revisi` tersimpan & terakumulasi; PDF revisi memuat halaman CATATAN REVISI di depan; dokumen non-revisi tidak memuatnya; versi lama jadi obsolete saat approve.

---

## Fase 4 — Tampilan (menu GL + kesejajaran field)

- **Sidebar `resources/views/layouts/app.blade.php`:**
  - GL: buang dropdown "Status Dokumen" (Semua/SOP/IK/SP/JSA) → jadi **menu tunggal** ke `documents.index`.
  - GL: tambah submenu **Dokumen Berlaku → Berlaku / Tidak Berlaku** (seperti SH/DH/PJO), navigasi read-only.
- **`DocumentController::index()`:** GL (group_leader, non view_all) hanya melihat **dokumen buatannya sendiri** (`created_by = user`) — bukan se-departemen. Non-Staff tetap read-only se-departemen.
- **`DocumentController::obsolete()`:** izinkan GL **melihat** daftar Tidak Berlaku dept-nya (read-only; tanpa hapus/obsolete). SH/DH/PJO tetap penuh.
- **SH/DH/PJO status dokumen staff** (poin tampilan #3) sudah ada (`staffStatus`, PJO lintas-dept) — diverifikasi saja.
- **Kesejajaran field (+/−)** `resources/views/documents/fields/_rich_list.blade.php` (juga dipakai reference_picker), `_user_picker.blade.php`, dan list bersarang di `_jsa_analysis.blade.php`: ganti `input-group` rapat menjadi baris **flex `gap-2`** dengan tombol kotak +/− **tinggi sama** dan **berjarak** dari kotak field (mengikuti gambar referensi langkah 2).

---

## Urutan & cara kerja
Kerjakan **berurutan Fase 0 → 4**, tiap fase: implementasi → jalankan test → **berhenti & lapor** untuk review + commit (CLAUDE.md §5). Fase 0 (renewal) dijalankan lebih dulu agar mulai dari data bersih.

## Verifikasi menyeluruh
- **Test suite:** `php artisan test` hijau tiap fase; `PrintLayoutTest` diperluas untuk kop-berulang, page_text, Edisi, dan halaman CATATAN REVISI (dibaca dari stream PDF nyata seperti pola yang sudah ada).
- **PDF nyata:** render JSA & SOP multi-halaman, verifikasi (a) kop atas tiap halaman, (b) "Halaman X dari Y", (c) Edisi, (d) blok info sekali, (e) halaman CATATAN REVISI hanya untuk dokumen revisi.
- **Manual:** login GL (menu tunggal + submenu Berlaku/Tidak Berlaku; hanya lihat dokumen sendiri); jalankan satu revisi penuh (tahap 3 muncul, versi lama jadi Tidak Berlaku, edisi/revisi naik sesuai roll-over).

## Catatan
- `docs/JSA new.docx`, `docs/jsa_pic/`, `docs/Lembar Revisi.docx`, `docs/lembar revisi/` masih untracked — akan ditanyakan apakah di-commit sebagai referensi.
- Usai selesai: perbarui **CLAUDE.md** (§7 penomoran halaman & Edisi JSA, §0/§4 roll-over edisi-revisi & lembar CATATAN REVISI, §6 visibilitas GL) dan `docs/aturan-alur-v2.md` bila perlu.
