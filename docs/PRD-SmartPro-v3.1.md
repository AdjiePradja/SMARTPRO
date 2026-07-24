# PRD — SmartPro Document Generator System — v3.1 (MERGED FINAL)
### PT. Putra Perkasa Abadi (PPA) — site PT. Adaro Indonesia

> **Versi:** v3.1 — **menggantikan v1.0, v2.0, v3.0 sepenuhnya.** Satu-satunya sumber kebenaran.
> **Sifat:** Living Document.
> **Konteks:** Development berjalan di Claude Code (~Fase 2). Dipakai untuk **menyelaraskan** kode yang ada, bukan mulai dari nol.
> **Tanggal:** 12 Juli 2026

---

## 0. Ringkasan perubahan v3.1 (atas v3.0)
Menyerap seluruh v3.0 + 12 poin feedback dari hasil nyata + 4 keputusan terakhir:
- **Preview langsung di panel kanan** (bukan tab/halaman baru), update saat klik tombol Preview, 1:1, scrollable — berlaku di form input & edit.
- **Visibilitas per level jabatan (bukan per-orang):** "Status Dokumen" menampilkan dokumen sesama level jabatan dalam dept yang sama. GL dapat sub-menu **read-only** "Status Dokumen Staff" (deptnya). Section Head dapat **read-only** status dokumen staff dept sendiri.
- **Dokumen ditolak TIDAK pindah ke "Dokumen Revisi" milik peninjau.** Peninjau memantau lewat **Tinjau Dokumen → bagian Status Revisi** dengan Action: Lihat, **Batalkan Revisi**.
- **Batalkan Revisi:** GL menarik penolakan. Dokumen `rejected` yang ditarik → kembali `in_review` (GL periksa ulang; mengantisipasi reviewer salah tulis). Dokumen revisi tipe B (0→1) yang dibatalkan → kembali **Berlaku**.
- **Halaman pengesahan PDF saat Berlaku:** kolom "Ditinjau Oleh" DAN "Disetujui Oleh" dua-duanya bertanda **Approved** (peninjau & pimpinan). Peran tetap terpisah (peninjau ≠ auto-approve).
- **Foto lampiran:** bisa dilihat saat ditinjau; bisa diberi komentar saat pembuatan/tinjau.
- **Feedback reviewer dua lapis:** rangkuman di atas + anotasi di samping tiap poin.
- **AI review lebih konkret:** resume dulu → highlight bagian bermasalah → saran; bila banyak, sajikan poin-poin.
- **Bug diperbaiki:** timezone salah (set **WITA/Asia/Makassar**), tombol Kembali saat edit → 403 (perbaiki otorisasi).
- **Tema visual baru** meniru arah **Soft UI-style** (referensi di `docs/referensi-visual/`) — lihat Bagian 12.
- **Logo bisa diklik → kembali ke home/dashboard.**
- **Menu Informasi Akun di semua role.**
- **Pimpinan tidak punya departemen** (hapus keterikatan dept; lintas-dept murni).

---

## 1. Ringkasan Sistem
Web internal menggenerate 4 dokumen mutu inti (SOP, SP, IK, JSA) untuk 7 departemen PPA site Adaro; alur Pembuatan → Peninjauan → Revisi → Persetujuan; format & penomoran otomatis konsisten; preview 1:1; output PDF; jejak audit; bantuan AI saat review.

**7 Departemen:** SHE, PLANT, HCGA, FWA (alias FALOG), ICTMD, PRODUKSI, ENGINEERING.
**Prinsip:** modular & maintainable. Jalan sempurna di XAMPP dulu. Kode direview senior IT → utamakan kode standar, jelas, terdokumentasi.

---

## 2. Konsep Inti: Schema-Driven Documents **[LOCKED]**
Tiap jenis dokumen didefinisikan **schema JSON** (di DB) = **sumber tunggal** untuk form, preview, PDF → keselarasan format terjamin. Schema bukan menebak; pengembang menulis tata letak eksplisit, engine menggambar sesuai schema.
- Kop/footer/pengesahan = **Blade partial bersama** (`_kop`, `_footer`, `_pengesahan`).
- Isi = **value_json per section** (tabel `document_contents`) — tak ada kolom tetap per jenis.
- **Urutan bangun:** SOP dulu (sampai PDF utuh) → IK → SP → **JSA terakhir**.
- Field types: text, textarea, rich_list (auto-number), table, repeatable_group, image (+komentar), radio/checkbox, signature, reference_picker, user_picker, auto.

---

## 3. Role, Jabatan, Alur Persetujuan **[LOCKED]**

### 3.1 Jabatan & Role
- **Jabatan:** `staff`, `group_leader`, `section_head`, `pimpinan`.
- **Role fungsional:** Pembuat, Auditor/Peninjau, Approver.
- **Admin IT:** full akses semua + kelola akun. Semua aksinya WAJIB masuk audit log.
- **Pimpinan TIDAK terikat departemen** (lintas-dept murni; tak punya sub-menu dept sendiri).

### 3.2 Aturan alur (ditentukan JABATAN PEMBUAT)
| Pembuat | Peninjau | Approver |
|---|---|---|
| **Staff** | **GL** (dept sama) | **Section Head ATAU Pimpinan** (dipilih pembuat via dropdown) |
| **GL** | **Section Head** | **Pimpinan** |

- Section Head **dual-role:** peninjau (bila pembuat GL) / approver (bila pembuat Staff). Hanya lihat dept sendiri.
- Hanya Pimpinan lihat semua dept. Peninjau & approver dipilih pembuat via dropdown (Nama+NRP+Dept).
- **Tidak boleh satu orang meninjau + menyetujui dokumen yang sama.**
- Pembuat bisa >1; **pembuat utama** (penekan "Buat Dokumen") satu-satunya TTD di pengesahan; tambahan hanya di log.

### 3.3 Visibilitas dokumen (per LEVEL JABATAN, bukan per-orang) **[LOCKED]**
- **"Status Dokumen"** (menu pembuat): menampilkan dokumen yang dibuat **siapa pun di level jabatan yang sama, dalam dept yang sama**. Contoh: Staff A lihat dokumen Staff B (dept sama); GL lihat dokumen sesama GL.
- **GL** punya sub-menu **"Status Dokumen Staff"** — **read-only**: lihat status dokumen staff deptnya (rejected/in_review/dll). **Tidak boleh Edit/Kirim/Hapus.**
- **Section Head** punya **"Status Dokumen Staff"** — **read-only**, **hanya dept sendiri**. (SH melihat dokumen GL lewat menu Tinjau Dokumen saat ditugaskan — bukan lewat menu status. Ini disengaja, bukan kelalaian.)
- **Dokumen Berlaku** di sub-menu departemen: dapat dilihat sesuai lingkup dept (dokumen final resmi). Aturan level tidak membatasi dokumen yang sudah terbit di menu departemen.
- **"Tinjau Dokumen"** tetap menampilkan dokumen **lintas-level** yang ditugaskan pada user untuk ditinjau/disetujui (independen dari aturan visibilitas di atas).

---

## 4. Lifecycle & Status Dokumen **[LOCKED]**

### 4.1 Status (kata, TANPA persentase)
- `draft` — belum dikirim. **Edit & Hapus** oleh pembuat (hanya di status ini).
- `waiting_for_review` — dikirim, belum disentuh reviewer. Pembuat **BISA menarik** (withdraw → draft).
- `in_review` — reviewer sudah mulai. Pembuat tak bisa withdraw/edit.
- `rejected` — ditolak. Muncul di **Dokumen Revisi** milik pembuat.
- `pending_approval` — lolos tinjau, menunggu approver.
- `Berlaku (Aktif)` — disetujui, final, tampil di sub-menu departemen.
- `Sedang Direvisi` — dokumen berlaku sedang diperbarui (revisi tipe B); versi lama Berlaku sementara.
- `obsolete` — versi lama setelah revisi tipe B selesai (arsip).

### 4.2 Alur
```
[Pembuat] isi (2 step, autosave, preview kanan) → SIMPAN(draft) / KIRIM
   │ KIRIM (pilih peninjau+approver) → waiting_for_review (masih bisa ditarik)
   ▼ reviewer buka → in_review
[Peninjau] review per-item + AI assist
   ├── lolos → pending_approval → [Approver]
   │              ├── setuju → Berlaku (PDF pengesahan: peninjau & pimpinan = Approved)
   │              └── tolak (feedback wajib) → balik ke GL → rejected (peninjau yg meloloskan dinotifikasi)
   └── tolak → anotasi per-item → rejected → menu Dokumen Revisi pembuat
                 (peninjau MEMANTAU di Tinjau Dokumen → Status Revisi; bisa "Batalkan Revisi")
```

### 4.3 Batalkan Revisi **[LOCKED]**
- Peninjau (GL) memantau dokumen yang ia tolak di **Tinjau Dokumen → Status Revisi** (kolom: tahap pengerjaan, Lihat, **Batalkan Revisi**).
- **Batalkan Revisi pada dokumen `rejected`** → menarik penolakan → dokumen kembali **`in_review`** (GL periksa ulang; mengantisipasi reviewer salah tulis audit).
- **Batalkan Revisi pada dokumen tipe B (`Sedang Direvisi`)** → batal diperbarui → kembali **Berlaku** (versi lama tetap aktif).

### 4.4 Dua jenis revisi **[LOCKED — jangan disatukan]**
**Tipe A — ditolak (belum terbit).** No revisi tetap 0; `revision_round` internal naik. Mulai dari **menu Dokumen Revisi** pembuat. Kolom: Status(Rejected), Feedback(rangkuman anotasi), Action(Revisi, Lihat PDF). "Revisi" buka form + tampilkan highlight & komentar; anotasi lama tetap terlihat selama perbaikan.

**Tipe B — pembaruan dokumen Berlaku (0→1→…).** Mulai dari **sub-menu Departemen → jenis → index → Ajukan Revisi** (GL/Pimpinan/Admin). Dokumen lama → Sedang Direvisi (Berlaku sementara); versi baru (No Revisi naik), review dari awal (anotasi lama tak dibawa). Approved → versi lama obsolete (arsip `document_versions`), versi baru Berlaku.

### 4.5 Reject oleh approver
Feedback wajib → kembali ke **GL** → GL tindak lanjut (review per-anotasi / full feedback) → rejected mengulang alur. Peninjau yang meloloskan dinotifikasi.

---

## 5. Penomoran Dokumen **[LOCKED]**
- Format: `PPA-ADRO-{JENIS}-{DEPT}-{NN}`.
- **Nomor SEMENTARA** berlabel jelas ("Nomor sementara — final setelah disetujui") saat pembuatan/review.
- **Nomor FINAL dikunci permanen saat approved (Berlaku).** Hanya dokumen terbit yang memakan nomor final → **tidak ada nomor final bolong.**
- Toggle Input Manual (validasi unik).

---

## 6. Proses Pengisian (2 Step) + Preview Panel Kanan **[LOCKED]**
- **Langkah 1:** header (no dokumen sementara + toggle manual, judul, jenis, dept auto, pembuat auto) + Tujuan + Ruang Lingkup + Referensi + Definisi.
- **Langkah 2:** Aktivitas & Tanggung Jawab + Lampiran (+komentar) + pilih Peninjau & Approver (dropdown Nama+NRP+Dept).
- Tombol tiap langkah: `Kembali` · `Preview` · `Langkah Berikutnya`.
- Langkah terakhir: `Kembali` · `Preview` · `Simpan` · `Kirim`.
- **PREVIEW PANEL KANAN:** klik tombol Preview → panel kanan **update langsung** menampilkan dokumen **1:1 format cetak**, **scrollable**. **BUKAN tab/halaman baru.** Berlaku di semua halaman ber-tombol-Preview (form input, edit).
- **Dialog konfirmasi (SweetAlert-style)** sebelum Kirim ("tak bisa diedit setelah dikirim"), Hapus, Ajukan Revisi, Batalkan Revisi.
- Setelah Simpan/Kirim → kembali ke **index pembuatan** (tabel dokumen).
- **Bug fix:** tombol **Kembali saat edit tidak boleh 403** — perbaiki otorisasi rute edit/kembali.

### 6.1 Tabel index pembuatan
Kolom: No Dokumen(sementara), Judul, Jenis, Status, Action(Edit/Hapus — **hanya draft**), Kirim(draft)/Tarik(waiting_for_review), Lihat PDF(tab baru).

### 6.2 Lampiran foto
Teks ATAU gambar (JPG/PNG maks 2MB), **bisa dilihat saat ditinjau**, **bisa dikomentari** (pembuatan & tinjau). Simpan per dept: `storage/app/public/lampiran/{DEPT}/{JENIS}/`.

---

## 7. Struktur Menu per Role **[LOCKED]**
> Setiap role punya **Informasi Akun**. Logo di header **bisa diklik → dashboard/home**.

### 7.1 STAFF (Pembuat)
Dashboard · **Buat Dokumen**(sub: SOP,SP,IK,JSA) · Dokumen Revisi · **Status Dokumen** *(dokumen sesama Staff dept sama; notif lonceng saat approved)*
— **{NAMA DEPT}** — {Nama Dept}(sub 4 — Berlaku) · Dokumen Independen(lintas-dept + khusus dept)
— **Informasi** — Informasi Akun

### 7.2 GROUP LEADER (Pembuat + Auditor)
Dashboard
— **Pembuatan Dokumen** — Buat Dokumen(sub 4) · Dokumen Revisi · **Status Dokumen**(sesama GL)
— **{NAMA DEPT}** — Tinjau Dokumen *(termasuk bagian Status Revisi: Lihat, Batalkan Revisi)* · **Status Dokumen Staff** *(read-only, dept ini)* · Status/Persetujuan Saya · {Nama Dept}(sub 4 — Berlaku, Action "Ajukan Revisi") · Dokumen Independen
— **Administrasi** — Persetujuan Akun · Audit Log
— **Informasi** — Informasi Akun

### 7.3 SECTION HEAD (Auditor + Approver) — TANPA menu pembuatan
Dashboard
— **Umum** — Tinjau Dokumen *(+ Status Revisi)* · **Status Dokumen Staff** *(read-only, hanya dept sendiri)* · Status/Persetujuan Saya · {Dept sendiri}(sub 4 — Berlaku) · Dokumen Independen(dept sendiri + lintas)
— **Administrasi** — Persetujuan Akun · Audit Log
— **Informasi** — Informasi Akun

### 7.4 PIMPINAN (Approver, lintas-dept, TANPA dept sendiri)
Dashboard
— **Umum** — Tinjau/Setujui Dokumen *(+ Status Revisi)* · Status/Persetujuan Saya · **Seluruh Departemen**(tiap dept sub 4 — lihat semua) · Dokumen Independen(semua)
— **Administrasi** — Persetujuan Akun · Audit Log
— **Informasi** — Informasi Akun

### 7.5 ADMIN IT
Semua menu + Manajemen User (buat GL/SH/Pimpinan, approve user dept) + akses penuh. Semua aksi tercatat audit log.

---

## 8. Dashboard **[LOCKED]**
- **Sapaan personal:** nama user + selamat pagi/siang/malam (sesuai jam **WITA**) + "selamat bekerja".
- **Kartu ringkasan angka per status** — dihitung dari tabel `documents` (keadaan sekarang), difilter hak akses.
- **Feed aktivitas terbaru** — dari `audit_logs`.
- (Grafik statistik bila relevan per role.)
- Sumber: angka dari `documents`, feed dari `audit_logs` — jangan tertukar.

---

## 9. Timeline per Dokumen **[LOCKED]**
Halaman detail dokumen (via "Lihat") berisi **timeline vertikal** riwayat (Dibuat→Dikirim→Ditinjau[hasil]→Direvisi→Disetujui), dibaca dari `audit_logs`. **Audit Log tetap menu terpisah** untuk penelusuran formal.

---

## 10. Notifikasi Lonceng (per role, in-app) **[LOCKED]**
- **Pembuat:** dokumennya rejected / approved(Berlaku).
- **Peninjau:** ada dokumen perlu ditinjau; dokumen yang ia loloskan ditolak approver.
- **Approver:** ada dokumen perlu disetujui.

---

## 11. AI Review Assist **[LOCKED arsitektur; implementasi pasca-MVP]**

### 11.1 Abstraksi provider (switchable Gemini → Claude/high-end)
Semua interaksi lewat **interface `AiReviewerInterface`**; implementasi awal `GeminiReviewer`; ganti provider = ganti binding/config, tanpa sentuh logika review. Input/output distandarkan (JSON temuan). Key di `.env`.

### 11.2 Cara AI membantu peninjau (WAJIB pola ini)
Bayangkan peninjau membaca dokumen panjang. AI harus:
1. **Resume/ringkasan singkat** isi dokumen dulu (konteks cepat).
2. **Highlight bagian yang salah/kurang tepat** — tunjuk bagian spesifik.
3. **Saran perbaikan** untuk tiap temuan.
4. Bila temuan banyak → **sajikan sebagai poin-poin terstruktur**, bukan paragraf panjang. Saran juga boleh berupa poin.

### 11.3 UX & akuntabilitas
- Saran AI **ditandai jelas sebagai dari AI** (badge/ikon), tak menyamar jadi komentar manusia.
- Reviewer per saran: **adopsi / edit lalu adopsi / tolak**.
- Yang ke user = **anotasi reviewer biasa** (tanggung jawab reviewer); asal-usul AI tercatat di audit log (`ai_generated`, `ai_adopted`).
- AI **tidak memblokir** (bila error/lambat, reviewer tetap manual). AI **tak pernah approve/reject sendiri**.
- 🟡 Konfirmasi izin kepatuhan pengiriman isi dokumen ke API eksternal (ISO 27001) sebelum aktivasi.

---

## 12. UX/UI & Tema Visual **[LOCKED]**

### 12.1 Arah visual (meniru Soft UI-style)
Referensi di **`docs/referensi-visual/`** (folder `pages` = contoh HTML; `assets` = css/js/img). **Gunakan aset yang bisa diintegrasikan; bila ada yang bentrok dengan Bootstrap 5/Alpine, HENTIKAN dan laporkan — jangan paksakan.** Lisensi dinyatakan aman oleh pemilik proyek.
Tiru: gaya **tabel**, **badge status**, **header bar**, **sidebar**, **card**, spacing lega, tipografi bersih. Kesan: bersih, modern, profesional — bukan tampilan generik.

### 12.2 Palet & tema
- **Tema terang default + toggle dark** (keduanya wajib mulus).
- Palet selaras identitas PPA bila memungkinkan (logo PPA merah) — arah gaya dari referensi, warna disesuaikan agar cocok konteks perusahaan tambang. (Konfirmasi akhir warna saat implementasi bila perlu.)

### 12.3 Standar UX yang tetap wajib
Notifikasi lonceng, SweetAlert-style confirm sebelum aksi, dark theme, hover, transisi halus, skeleton/loading state, empty state, breadcrumb, badge status berwarna, kolom Status+Action+Lihat PDF konsisten. **Nol emoji** → Bootstrap Icons / vector. **Logo bisa diklik → home.**

### 12.4 Feedback reviewer ke pembuat (dua lapis)
- **Rangkuman review di atas** (ikhtisar keseluruhan).
- **Anotasi di samping tiap poin** yang bermasalah (komentar per-item, sejajar dengan bagian dokumen).
Pembuat bisa lompat dari rangkuman ke anotasi terkait.

### 12.5 Preview
Panel kanan, 1:1, scrollable, update saat klik Preview. (Lihat Bagian 6.)

### 12.6 Fokus & teknologi
Desktop dulu (mobile/wrapper pasca-MVP). Bootstrap 5 + Alpine.js (tanpa SPA framework).

---

## 13. Konvensi Kode **[LOCKED]**
- Controller & Model **standar Laravel** (`DocumentController`, `Document`). DILARANG prefix C/M.
- Blade seragam `{jenis}/{aksi}`: `resources/views/sop/index.blade.php`, `sop/create.blade.php`, `sop/edit.blade.php`.
- Route seragam: `sop.index`, `sop.create`, `sop.edit`.
- Logika di Service class. Validasi via Form Request. Otorisasi via Policy/Gate. PSR-12.
- **Timezone aplikasi: `Asia/Makassar` (WITA)** — set di config global.

---

## 14. Model Data (ERD Konseptual)
- **users** (id, nrp[username], password, name, jabatan, department_id[nullable utk pimpinan], status)
- **departments** (id, code, name, alias)
- **document_types** (id, code, name, schema_json, class)
- **documents** (id, doc_number_temp, doc_number_final, document_type_id, department_id, title, status, no_revisi, current_revision_round, is_controlled, created_by, published_at) — soft delete
- **document_authors** (document_id, user_id, is_primary)
- **document_contents** (id, document_id, section_key, value_json)
- **document_versions** (id, document_id, no_revisi, snapshot_json, created_at)
- **reviews** (id, document_id, reviewer_id, revision_round, decision, summary)
- **review_annotations** (id, review_id, section_key, item_ref, comment, ai_generated, ai_adopted)
- **attachment_comments** (id, attachment_id, user_id, comment) — komentar pada foto lampiran
- **approvals** (id, document_id, approver_id, decision, comment, signed_at)
- **attachments** (id, document_id, section_key, path, mime, size)
- **notifications** (Laravel default)
- **audit_logs** (id, user_id, document_id, action, meta_json, created_at) — wajib, termasuk aksi admin & asal-usul saran AI

---

## 15. Nice-to-Have / Pasca-MVP (data disiapkan, kerjakan belakangan)
- **T1 Reassign** peninjau/approver oleh Admin untuk dokumen tersangkut.
- **T5 Perbandingan versi (diff)** saat revisi (snapshot sudah disiapkan).
- **Responsif mobile + wrapper mobile.**
- **AI Review Assist** implementasi (arsitektur disiapkan sekarang).
- Export Word, OCR input existing, PDF→Word, live-preview-keystroke.
- Schema + blade cetak **IK, SP, JSA** — 🟡 menunggu contoh (JSA terakhir). **Jangan dikarang.**
- Dokumen independen (per-dept & lintas-dept) — 🟡 menunggu contoh. Struktur menu disiapkan, isi kosong.

---

## 16. Referensi (WAJIB dilihat) — folder `docs/`
- **PRD-SmartPro-v3.1.md** (ini, sumber kebenaran)
- **INSTRUKSI-Merging-Berfase-v3.1.md** (urutan kerja)
- **schema-sop.json** (cetak biru SOP)
- **SOP-contoh.pdf** — acuan format cetak & pengesahan
- **screenshot form Langkah 1 & 2**, **screenshot review JSA** — acuan UX
- **referensi-visual/** (pages + assets) — acuan tema (Soft UI-style)
- **Logo:** `public/images/logo-ppa.png`
- IK/JSA/SP & dokumen independen — menyusul, jangan dikarang.

---

## 17. Changelog
- **v3.1 (12 Jul 2026):** + preview panel kanan, visibilitas per level jabatan (+ Status Dokumen Staff read-only utk GL & SH), Batalkan Revisi (rejected→in_review; tipe B→Berlaku), pengesahan PDF dua kolom Approved, foto lampiran dapat dilihat+dikomentari saat tinjau, feedback dua lapis, AI pola resume→highlight→saran-poin, fix timezone WITA & bug 403 tombol kembali, tema Soft UI-style (referensi-visual), logo klik→home, Informasi Akun semua role, Pimpinan tanpa dept. Menggantikan v1.0/v2.0/v3.0.
