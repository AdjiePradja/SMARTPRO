# PRD — SmartPro Document Generator System — v2.0
### PT. Putra Perkasa Abadi (PPA) — site PT. Adaro Indonesia

> **Versi:** v2.0 (menggantikan v1.0) · **Sifat:** Living Document
> **Konteks penting:** Development sudah berjalan di Claude Code (sekitar Fase 2). PRD ini + instruksi merging dipakai untuk **menyelaraskan** kode yang sudah ada, bukan memulai dari nol.
> **Tanggal:** 12 Juli 2026

---

## 0. Yang berubah dari v1.0 (baca dulu)
- Proses pengisian **2 step** (bukan 5).
- Login pakai **NRP + password** (bukan email).
- **3 role** (Pembuat, Auditor/Peninjau, Approver) + **Admin**; **4 jabatan** (Staff, GL, Section Head, Pimpinan). Alur ditentukan **jabatan pembuat**.
- Status persetujuan **tanpa persentase** (0/50/100 dihapus — rancu). Pakai status kata yang jelas.
- Dua jenis revisi dipisah tegas (tipe A ditolak, tipe B pembaruan 0→1).
- Tema **terang default + toggle dark**. **Nol emoji** — pakai **Bootstrap Icons** / vector.
- Struktur menu final per role (Bagian 7).
- Konvensi: controller/model **standar Laravel**; Blade & route **seragam** pola `{Jenis}{Aksi}` / `{jenis}.{aksi}`.

---

## 1. Ringkasan
Aplikasi web internal untuk menggenerate dokumen mutu (SOP, JSA, IK, SP) bagi 7 departemen PPA site Adaro, dengan alur Pembuatan → Peninjauan → Revisi → Persetujuan, format & penomoran otomatis konsisten, preview 1:1, output PDF, jejak audit, dan bantuan AI saat review.

**7 Departemen:** SHE, PLANT, HCGA, FWA (alias FALOG), ICTMD, PRODUKSI, ENGINEERING.

---

## 2. Role, Jabatan, dan Alur Persetujuan **[LOCKED]**

### 2.1 Role (hak akses) & Jabatan (identitas struktural)
- **Jabatan** (muncul di dokumen & dropdown): `staff`, `group_leader`, `section_head`, `pimpinan`.
- **Role fungsional** diturunkan dari jabatan + konteks: **Pembuat**, **Auditor/Peninjau**, **Approver**.
- **Admin IT**: full akses semua (kelola akun + semua aksi dokumen). **Semua aksinya WAJIB masuk audit log.**

### 2.2 Aturan alur (ditentukan jabatan PEMBUAT) **[LOCKED]**

| Pembuat | Peninjau (Auditor) | Approver |
|---|---|---|
| **Staff** | **GL** (dept sama) | **Section Head ATAU Pimpinan** (dipilih pembuat via dropdown) |
| **GL** | **Section Head** | **Pimpinan** |

- **Section Head bersifat dual:** jadi **Peninjau** bila pembuat = GL; jadi **Approver** (alternatif Pimpinan) bila pembuat = Staff.
- Peninjau & approver **dipilih pembuat lewat dropdown** saat pengisian (dropdown menampilkan **Nama + NRP + Dept**).
- Prinsip terjaga: **tidak ada satu orang meninjau + menyetujui dokumen yang sama.**

### 2.3 Pembuat ganda (multi-author) **[LOCKED]**
- Pembuat bisa lebih dari 1.
- **Pembuat utama = user yang menekan "Buat Dokumen".** Hanya pembuat utama yang tampil di "Dibuat Oleh" pada halaman pengesahan PDF (mengikuti format SOP contoh).
- Pembuat tambahan **hanya dicatat di log/metadata**, tidak tanda tangan di PDF.

---

## 3. Document Lifecycle & Status **[LOCKED]**

### 3.1 Alur
```
[Pembuat] Isi dokumen (2 step, autosave, preview) → SIMPAN (draft) atau KIRIM
   │ KIRIM (pilih peninjau & approver via dropdown)
   ▼
[IN_REVIEW] → [Peninjau] review per-item + AI assist
   ├── lolos ──► [PENDING_APPROVAL] ──► [Approver]
   │                                       ├── setuju ─► [PUBLISHED / BERLAKU]
   │                                       └── tolak (feedback) ─► balik ke GL ─► [REJECTED]
   └── ada temuan ─► anotasi per-item ─► [REJECTED] ─► balik ke pembuat (Dokumen Revisi)
```

### 3.2 Status dokumen (kata, bukan persentase)
- `draft` — disimpan, belum dikirim. **Bisa Edit & Hapus** oleh pembuat.
- `in_review` — sedang ditinjau auditor. Pembuat **tidak bisa** edit/hapus.
- `rejected` — ditolak (oleh peninjau atau approver). Muncul di **Dokumen Revisi** pembuat/GL.
- `pending_approval` — lolos tinjau, menunggu approver.
- `published` / **Berlaku (Aktif)** — disetujui, final, tampil di sub-menu departemen.
- **Sedang Direvisi** — dokumen berlaku sedang diperbarui (revisi tipe B); versi lama tetap Berlaku sementara.
- `obsolete` — versi lama setelah revisi tipe B selesai (diarsip, tak dihapus).

### 3.3 Dua jenis revisi **[LOCKED — jangan disatukan]**

**Revisi Tipe A — akibat ditolak (belum pernah terbit).**
- No. Revisi tetap 0; hanya `revision_round` internal naik.
- **Titik mulai:** menu **Dokumen Revisi** milik pembuat (atau GL bila reject datang dari approver).
- Kolom tabel: Status (`Rejected`), **Feedback** (rangkuman anotasi peninjau, ditulis sesuai anotasi), Action (**Revisi**, **Lihat PDF**).
- Saat "Revisi": buka lagi form pengisian, **tampilkan bagian yang di-highlight + komentar peninjau** agar pembuat tahu persis mana yang salah.
- Anotasi lama **tetap terlihat selama proses perbaikan** (agar pembuat & peninjau tahu mana yang sudah dibenahi).

**Revisi Tipe B — pembaruan dokumen berlaku (0 → 1 → …).**
- Dokumen sudah `published/Berlaku`. Perlu diperbarui.
- **Titik mulai:** **Sub-menu Departemen → jenis dokumen → tabel index → Action "Ajukan Revisi".**
- Yang berwenang mengajukan: **GL, Pimpinan, Admin** (default). Staff tidak langsung.
- Saat diajukan: dokumen lama → status **Sedang Direvisi** (masih Berlaku sementara); dibuat versi baru mewarisi nomor dokumen, No. Revisi naik.
- Versi baru **di-review dari awal** (anotasi lama TIDAK dibawa — mulai bersih).
- Setelah versi baru approved: versi lama → `obsolete` (arsip), versi baru → Berlaku.
- **Snapshot tiap versi disimpan di `document_versions`** (wajib untuk jejak audit).

### 3.4 Reject oleh approver **[LOCKED]**
- Approver menolak → wajib isi **feedback/komentar** → dokumen kembali ke **GL**.
- GL menindaklanjuti: mengisi review per-anotasi yang perlu diperbaiki, atau memberi full feedback → dokumen jadi `rejected` dan mengulang alur.
- **Peninjau yang tadi meloloskan diberi notifikasi** bahwa dokumen yang ia loloskan ditolak approver.

---

## 4. Proses Pengisian (2 Step) **[LOCKED]**

### 4.1 Step & tombol
- **Langkah 1:** header (no dokumen auto + toggle manual, judul, jenis, departemen auto, pembuat auto) + Tujuan + Ruang Lingkup + Referensi + Definisi.
- **Langkah 2:** Aktivitas & Tanggung Jawab + Lampiran + Verifikasi/Approval (pilih peninjau & approver).
- **Tombol tiap langkah:** `Kembali` · `Preview` · `Langkah Berikutnya`.
- **Tombol langkah terakhir:** `Kembali` · `Preview` · `Simpan` · `Kirim`.
- **Preview:** panel/modal 1:1 format cetak, scrollable, full-screen-able. Memakai partial bersama `_kop`, `_footer`, `_pengesahan`.
- Setelah **Simpan** atau **Kirim** → kembali ke **index pembuatan** yang menampilkan tabel dokumen yang telah dibuat.

### 4.2 Tabel index pembuatan
Kolom: No Dokumen, Judul, Jenis, Status, **Action** (Edit & Hapus — **hanya untuk status `draft`**), **Kirim** (untuk draft), **Lihat PDF** (buka tab baru, selalu ada).

### 4.3 Lampiran foto **[LOCKED]**
- Lampiran mendukung teks ATAU gambar (JPG/PNG, maks 2MB) — sesuai format SOP.
- **Simpan file lampiran per jenis departemen** di folder terpisah: `storage/app/public/lampiran/{DEPT}/{JENIS}/...`.

---

## 5. Template, Logo, Penomoran **[LOCKED]**
- **Logo PPA:** `public/images/logo-ppa` (mis. `public/images/logo-ppa.png`). Dipakai semua partial kop.
- **Partial bersama:** `_kop`, `_footer` (teks: "Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak."), `_pengesahan`.
- **Penomoran:** `PPA-ADRO-{JENIS}-{DEPT}-{NN}` auto + toggle manual (validasi unik).
- **PDF:** DomPDF (default). Cetak SOP wajib mendekati format dokumen SOP contoh (lihat referensi).

---

## 6. Kategori Dokumen **[LOCKED]**
- **Dokumen Inti:** SOP, SP, IK, JSA — semua dept, format seragam.
- **Dokumen Independen:** per-dept (mis. HIRADC SHE) + lintas-departemen (berita acara, form peminjaman, notulen). 🟡 **Struktur menu disiapkan, isi kosong sampai ada contoh — JANGAN dikarang.**
- **Terkendali** (tidak disebarluaskan) vs **Tidak Terkendali** (sudah disebarluaskan) — atribut/notes.

---

## 7. Struktur Menu per Role **[LOCKED]**

### 7.1 STAFF (Pembuat)
- Dashboard
- **Buat Dokumen** → sub: SOP, SP, IK, JSA
- Dokumen Revisi
- Status Dokumen *(notif lonceng saat approved)*
- **— {NAMA DEPT USER} —**
  - {Nama Dept} → sub: SOP, SP, IK, JSA *(berisi dokumen yang sudah approved/Berlaku)*
  - Dokumen Independen *(sub: lintas-dept + khusus dept tsb)*
- **— Informasi —**
  - Informasi Akun

### 7.2 GROUP LEADER (Pembuat + Auditor)
- Dashboard
- **— Pembuatan Dokumen —**
  - Buat Dokumen → sub: SOP, SP, IK, JSA
  - Dokumen Revisi
  - Status Dokumen
- **— {NAMA DEPT USER} —**
  - Tinjau Dokumen *(antrian yang perlu ditinjau)*
  - **Status/Persetujuan Saya** *(dokumen yang menunggu tindakan / sedang diproses olehnya)*
  - {Nama Dept} → sub: SOP, SP, IK, JSA *(approved; ada Action "Ajukan Revisi")*
  - Dokumen Independen *(lintas-dept + khusus dept)*
- **— Administrasi —**
  - Persetujuan Akun
  - Audit Log
- **— Informasi —**
  - Informasi Akun

### 7.3 SECTION HEAD (Auditor + Approver) — **TANPA menu pembuatan dokumen**
- Dashboard
- **— Umum —**
  - Tinjau Dokumen
  - **Status/Persetujuan Saya**
  - {Nama Dept-nya sendiri} → sub: SOP, SP, IK, JSA *(hanya dept sendiri)*
  - Dokumen Independen *(dept sendiri + lintas-dept)*
- **— Administrasi —**
  - Persetujuan Akun
  - Audit Log
- **— Informasi —**
  - Informasi Akun

> Section Head **hanya melihat dept-nya sendiri.**

### 7.4 PIMPINAN (Approver, akses luas)
- Dashboard
- **— Umum —**
  - Tinjau/Setujui Dokumen
  - **Status/Persetujuan Saya**
  - **Seluruh Departemen** → tiap dept punya sub: SOP, SP, IK, JSA *(lihat semua 4 dokumen inti lintas dept)*
  - Dokumen Independen *(semua: lintas-dept + khusus tiap dept)*
- **— Administrasi —**
  - Persetujuan Akun
  - Audit Log
- **— Informasi —**
  - Informasi Akun

> **Hanya Pimpinan** yang melihat semua dept & seluruh kategori dokumen per departemen.

### 7.5 ADMIN IT
- Semua menu di atas + Manajemen User (buat GL/SH/Pimpinan, approve user dept) + akses penuh semua dept & aksi. Semua aksi tercatat audit log.

---

## 8. Notifikasi Lonceng (per role) **[LOCKED]**
- **Pembuat:** dokumennya `rejected` atau `approved/Berlaku`.
- **Peninjau:** ada dokumen baru yang perlu ditinjau; dokumen yang ia loloskan ditolak approver.
- **Approver:** ada dokumen yang perlu disetujui.
- In-app saja (tanpa email untuk MVP).

---

## 9. AI Review Assist **[LOCKED]**
Summarize + highlight ketidaksesuaian + saran. Reviewer bisa **adopsi / custom / tolak** saran; yang diadopsi jadi anotasi per-item ke pembuat. Provider dapat diganti (interface). AI tak pernah approve/reject sendiri. 🟡 Konfirmasi izin kepatuhan pengiriman data ke API AI eksternal.

---

## 10. UX/UI **[LOCKED]**
- **Tema terang default + toggle dark.**
- **Nol emoji.** Gunakan **Bootstrap Icons** atau vector SVG yang senada tema.
- Modern, clean, interaktif: hover, transisi halus, skeleton loading, empty state & loading state dirancang.
- **Kejelasan status & konteks di mana-mana:** badge status berwarna (Draft/In Review/Rejected/Pending/Berlaku/Sedang Direvisi), breadcrumb, judul halaman jelas, tabel dengan kolom Status + Action + Lihat PDF yang konsisten.
- Preview selalu tersedia di alur pembuatan.
- Bootstrap 5 + Alpine.js (tanpa SPA framework).

---

## 11. Konvensi Kode **[LOCKED]**
- **Controller & Model: standar Laravel** (`DocumentController`, `Document`). TIDAK pakai prefix `C`/`M` (menghindari rename massal & menjaga fitur Laravel; kode akan direview senior IT).
- **Blade seragam:** `resources/views/sop/index.blade.php`, `sop/create.blade.php`, `sop/edit.blade.php` (pola `{jenis}/{aksi}`); komponen view boleh dinamai `SopIndex`, `SopCreate`, `SopEdit` bila memakai class component.
- **Route seragam:** `sop.index`, `sop.create`, `sop.edit`, dst.
- Logika di **Service class**. Validasi via **Form Request**. Otorisasi via **Policy/Gate**.

---

## 12. Model Data (ERD Konseptual)
users (id, **nrp** [username], password, name, jabatan[staff/group_leader/section_head/pimpinan], department_id, status[pending/active/rejected]) · departments · document_types(schema_json) · documents(doc_number, status, no_revisi, current_revision_round, is_controlled, created_by) · document_authors(document_id, user_id, is_primary) · document_contents(section_key, value_json) · document_versions(no_revisi, snapshot_json) · reviews · review_annotations(section_key, item_ref, comment, ai_generated, ai_adopted) · approvals(decision, comment) · attachments(path per dept/jenis) · notifications · audit_logs(user_id, action, meta_json).
Soft delete pada documents.

---

## 13. Referensi (WAJIB dilihat saat implementasi)
- **Dokumen SOP contoh (PDF terisi)** — acuan format cetak & halaman pengesahan. Taruh di `docs/`.
- **Screenshot form aplikasi** (Langkah 1 & 2) & **screenshot review JSA** — acuan UX form & tabel review. Taruh di `docs/`.
- **Logo:** `public/images/logo-ppa`.
- **schema-sop.json** — cetak biru SOP.
- 🟡 **IK, JSA, SP** — contoh menyusul; schema & blade cetaknya dibuat saat contoh tersedia. JANGAN dikarang.

---

## 14. Changelog
- **v2.0 (12 Jul 2026):** 3 role + 4 jabatan, alur per jabatan pembuat, Section Head dual-role, status tanpa %, dua jenis revisi dipisah (titik mulai berbeda), 2-step, NRP login, tema+dark, nol emoji/Bootstrap Icons, menu final per role, konvensi Laravel standar + Blade/route seragam, lampiran foto per dept, logo path. Untuk merging dengan kode Fase 2 yang sudah ada.
