# HANDOVER SmartPro — Konteks Lengkap untuk Sesi Baru

> **Baca file ini lebih dulu di awal sesi baru.** Ini rangkuman padat SELURUH
> perjalanan: konteks produk, aturan keras, peran & alur, arsitektur cetak PDF,
> pengetahuan DomPDF yang didapat dari pengukuran, kalibrasi terkini, testing,
> dan status file. Sumber kebenaran resmi tetap: `CLAUDE.md` (teknis) +
> `docs/PRD-SmartPro-v3.1.md` (produk). File ini MELENGKAPI keduanya dengan
> hal yang tidak tercatat di sana (terutama seluk-beluk cetak PDF).

Terakhir diperbarui: sesi cetak-v11 (font kop 14/12, pengesahan halaman terpisah, head-block JSA
ramping, **mesin paginasi JSA 2-fase context-carry**). 54 test hijau.

---

## 0. Cara Kerja & Aturan Keras (WAJIB, tanpa konfirmasi eksplisit = JANGAN)

- **JANGAN** `migrate:fresh` / `migrate:refresh` atau apa pun yang MENGHAPUS DATA — TANYA dulu.
- **JANGAN** commit `.env` (pastikan di `.gitignore`).
- **JANGAN** hapus file/folder tanpa konfirmasi.
- **JANGAN** install package besar tanpa izin + alasan.
- **JANGAN** kerjakan banyak fase sekaligus. Satu fase → BERHENTI → tunggu review + commit.
- **JANGAN** mengarang struktur dokumen tanpa contoh dari owner.
- Belum jelas → TANYA. DB berubah → tunjukkan migration, tunggu approval.
- Bahasa kerja: **Indonesia**. Ikuti gaya kode sekitarnya (PSR-12, standar Laravel).
- Owner adalah pengembang magang; kode direview senior IT → utamakan kode STANDAR, jelas, terdokumentasi.

### File referensi yang HARUS tetap LOKAL / untracked (jangan commit — "referensi harga mati")
- `docs/JSA new.docx`, `docs/JSA.docx`, `docs/Lembar Revisi.docx`
- `docs/jsa_pic/`, `docs/lembar revisi/`, `docs/refrensi revisi/`, `docs/clauderev.md`
- **`template_ik.blade.php`, `template_jsa.blade.php`, `template_sop.blade.php`,
  `template_sp.blade.php`, `HANDOVER_PDF_SMARTPRO.md`** (di root) — template referensi
  visual dari project lama `Sistem_managemen_dokumen_prosedur`. HANYA acuan tampilan.

---

## 1. Konteks Produk

Web internal **PT Putra Perkasa Abadi (site Adaro)** untuk menggenerate dokumen mutu
(**SOP, IK, SP, JSA**) untuk **7 departemen**. Development sudah berjalan (~Fase 2) —
tugas = MENYELARASKAN & memperbaiki, bukan mulai dari nol.

**Urutan bangun jenis dokumen:** SOP dulu (sampai PDF utuh) → IK → SP → JSA terakhir.
Isi dokumen = `value_json` per section (tabel `document_contents`) — **schema-driven**,
bukan kolom tetap per jenis.

---

## 2. Tech Stack (WAJIB, jangan menyimpang)

| Bagian | Pilihan |
|---|---|
| Framework | **Laravel 12** (PHP 8.2+) |
| View/UI | **Blade + Bootstrap 5 + Alpine.js** — DILARANG Vue/React/Inertia/Tailwind-SPA |
| DB | **MySQL 8** via XAMPP |
| Auth | **NRP + password** |
| RBAC | **spatie/laravel-permission** |
| PDF | **barryvdh/laravel-dompdf** v3.1.5 (DomPDF) |
| Ikon | **Bootstrap Icons** — NOL emoji |
| Timezone | **Asia/Makassar (WITA)** global |

Konvensi: Controller & Model standar (`DocumentController`, `Document`) — dilarang prefix C/M.
Blade `{jenis}/{aksi}` (sop/index, sop/create, sop/edit). Route `sop.index` dst.
Logika di Service class, validasi Form Request, otorisasi Policy/Gate.

---

## 3. Peran, Jabatan, Alur, Visibilitas

### Jabatan
- **staff** — label "Non-Staff", **READ-ONLY**.
- **group_leader (GL)** — **SATU-SATUNYA PEMBUAT**, posisi terendah alur. TIDAK meninjau,
  TIDAK bisa "Ajukan Revisi", TIDAK menonaktifkan dokumen.
- **section_head (SH)** dan **departemen_head (DH)** — DH = wewenang sama dengan SH.
- **pimpinan/PJO** — TANPA departemen. **PENYETUJU SAJA** (tak pernah meninjau),
  bisa meng-approve SEMUA, + menyetujui akun non-staff baru.
- **Admin** — full akses (tercatat audit log).

### Matriks Peninjau/Penyetuju (FINAL — dipusatkan di `DocumentParticipantResolver`)
1. **JSA dibuat GL dept SHE/PLANT** → reviewer: SH & DH SHE & PLANT; approver: **PJO** dan DH/SH.
2. **JSA dibuat dept selain SHE/PLANT** → reviewer: SH & DH dept-nya sendiri **plus** SH & DH SHE & PLANT;
   approver: **PJO** dan SH/DH dari SHE & PLANT.
3. **SOP** → review oleh SH/DH dept masing-masing; approve oleh **PJO saja**.
4. **IK & SP** → review oleh SH/DH dept masing-masing; approve oleh **PJO ATAU SH/DH dept masing-masing**.

> Catatan penting yang sempat salah berkali-kali: **PJO ITU HANYA APPROVER** — tidak
> pernah meninjau. Halaman persetujuan PJO = keputusan + alasan saja (bukan form per-item).

### Visibilitas & Menu
- **GL** "Status Dokumen" = HANYA dokumen buatannya sendiri (menu TUNGGAL, tanpa dropdown).
- **Non-Staff** = read-only se-departemen (dropdown per jenis).
- **SH/DH/PJO** = TANPA menu "Status Dokumen" (pakai "Status Dokumen Staff"; PJO lintas 7 dept).
- **Dokumen Berlaku** + submenu **Tidak Berlaku** untuk SH/DH/PJO/Admin & GL
  (GL read-only, tanpa hapus/nonaktif).

---

## 4. Status Dokumen & Alur Revisi

**Status:** `draft`, `waiting_for_review`, `in_review`, `rejected`, `pending_approval`,
`published` (Berlaku), `sedang_direvisi` (Sedang Direvisi), `obsolete` (Tidak Berlaku).

- Edit/Hapus HANYA saat `draft`. Withdraw HANYA saat `waiting_for_review` (belum ditinjau).
- **Batalkan Revisi** (oleh GL/peninjau di Tinjau Dokumen → Status Revisi):
  `rejected` → kembali `in_review`; dokumen Tipe B `sedang_direvisi` → kembali `published`.

### Dua tipe revisi
- **Tipe A** — dokumen ditolak; muncul di menu "Dokumen Revisi" pembuat.
- **Tipe B** — dokumen sudah Berlaku, "Ajukan Revisi" oleh **SH/DH/PJO/Admin** (BUKAN GL).
  Draft revisi dimiliki pembuat asli/GL. Versi lama disimpan `document_versions`,
  otomatis jadi `obsolete` saat versi baru disahkan.

### Roll-over Edisi/Revisi (Tipe B)
Revisi 0..4; revisi berikutnya = **Edisi+1, Revisi 0** (angka 5 TAK PERNAH tampil).
`DocumentService::nextEditionRevision(int $edisi, int $noRevisi): array`
→ `$noRevisi + 1 >= 5 ? [$edisi+1, 0] : [$edisi, $noRevisi+1]`.
Deteksi "ini revisi" via versi lama `sedang_direvisi` bernomor SAMA + kolom
`revises_document_id` — **BUKAN** `no_revisi > 0` (roll-over mengembalikan revisi ke 0).

### Log Revisi (draft Tipe B)
Wizard punya langkah EKSTRA "Log Revisi" di akhir (tombol Simpan/Kirim pindah ke sana).
Mengisi lembar **CATATAN REVISI** (kolom: `NO | NO. REV | TANGGAL REV | HAL. | CATATAN`,
format `docs/Lembar Revisi.docx`) yang tercetak di **halaman DEPAN PDF**; baris
terakumulasi lintas revisi. Disimpan via `DocumentService`/`persistRevisionLog()`.

### Reject oleh approver
Feedback WAJIB → dokumen balik ke GL (`rejected`); peninjau yang meloloskan dinotifikasi.
Alasan approver disimpan sebagai `Review` (`decision = needs_revision`, prefix `[Penyetuju]`)
agar tampil di form revisi pembuat.

---

## 5. Penomoran

Format `PPA-ADRO-{JENIS}-{DEPT}-{NN}`. **SEMENTARA** (berlabel "(sementara)") saat
pembuatan/review; **FINAL** dikunci saat `approved` (tak bolong). Toggle manual (validasi unik).
`DocumentNumberService::generateTemp()` memakai sequence terkecil-belum-terpakai
(termasuk soft-deleted/manual) — memperbaiki bug tabrakan nomor.
`generateFinal()` dipanggil saat approve (kecuali revisi Tipe B yang mewarisi nomor lama).

---

## 6. Arsitektur Cetak PDF (INTI — jangan sampai regresi)

### File
- **`resources/views/documents/print/render.blade.php`** — SOP/IK/SP (satu view bersama, portrait).
- **`resources/views/documents/print/render-jsa.blade.php`** — JSA (landscape).
- Partial: `_kop`, `_kop_jsa`, `_pengesahan`, `_footer`, `_catatan_revisi`.
- **`DocumentController`**:
  - `renderPdfDocument(Document): Dompdf` — render + stamp nomor halaman; dipakai `pdf()` DAN test.
  - `stampPageNumbers(Dompdf, $orientation)` — koordinat terkalibrasi:
    - portrait: `page_text(392.8, 94.5, 'Halaman: {PAGE_NUM} dari {PAGE_COUNT}', ...)`
    - landscape: `page_text(712.0, 90.9, '{PAGE_NUM} dari {PAGE_COUNT}', ...)`
  - `pdfStampDataUri()` — **TANPA proses GD** (owner: "STEMPEL APPROVAL JANGAN DI EDIT").
    Pakai `public/images/approve-stamp.png` apa adanya (base64).
- Logo: `public/images/logo-ppa.png` (bisa diklik → home).
- Cap **APPROVED** di semua TTD saat dokumen Berlaku — pakai `published_at`
  (agar dokumen `sedang_direvisi`/`obsolete` yang dulu disahkan tetap bercap).

### Prinsip tata letak
- **Kop BERULANG tiap halaman** via `position: fixed` (BUKAN `<thead>` page-frame:
  dgn thead, DomPDF memindahkan tabel utuh ke hal.2 → hal.1 bolong). Ruang kop
  dipesan lewat `@page { margin-top }`; kop ditarik ke area margin dgn `top` negatif.
- Tabel isi harus **top-level** (jangan bersarang di wrapper) agar bisa mengalir &
  terpecah antar halaman.
- **Alir-isi**, BUKAN page-break paksa tiap bab (owner menolak "page break yg tidak perlu";
  minta halaman DIHABISKAN dulu).
- Margin bawah **2cm (57pt)**; tak ada teks menembusnya.
- JSA: blok info+TTD hanya di halaman 1; tabel analisa header BERULANG tiap halaman
  (`thead { display: table-header-group }`).

### Kalibrasi terkini (cetak-v10)

**SOP/IK/SP — `render.blade.php`:**
- `@page { margin: 162pt 28pt 57pt 28pt }`; `.kop-fixed { top: -146pt }`.
- body **9pt**, `line-height: 1.5`.
- Kop lebar kolom (cm owner): logo **22.2%** (img 108pt) | jenis+judul **44.5%** | meta **33.3%**.
  Font judul WAJIB pakai selektor `table.kop td.kop-title`/`td.kop-subject` (lihat §7 gotcha CSS):
  **JENIS 14pt**, **JUDUL 12pt** (diperbesar dari 16/10 per permintaan owner).
- **Halaman pengesahan SELALU di halaman terpisah** (`.pengesahan-page { page-break-before: always }`) —
  tak digabung dgn ekor isi (permintaan owner #7).
- `.section-bar` padding `4pt 7pt`; `.section-box` padding `7pt 8pt`.
- `table.list` td 9pt; kolom nomor 26pt.
- `table.akt` (AKTIVITAS 75% | PIC 25%): nomor "5.x" DIGABUNG di sel sbg
  `<span class="jn">` (inline-block, `width:20pt; margin-left:-20pt`), sejajar sub-judul via
  `vertical-align: -3.3pt` (terkalibrasi utk 9pt). PIC di baris TENGAH grup. `overflow-wrap: anywhere`.
- `table.pengesahan` kolom 35/20/20/25.

**JSA — `render-jsa.blade.php`:**
- `@page { margin: 139pt 8pt 57pt 8pt }`; `.kop-fixed { top: -123pt }`.
- body **8pt**.
- Kop: logo **12.3%** (img 92pt) | judul **63.2%** (`td.kop-title` 16pt italic) | meta (9%+15.5%).
  No. Dokumen kop di-hardcode **`PPA-ADRO-F-SHE-03B`** (nomor formulir SHE). Tgl Efektif dari
  widget tanggal → format Indonesia. Nomor pekerjaan/JSA kita tampil di baris "No. Pekerjaan/JSA".
- `head-block` DIRAMPINGKAN (permintaan owner) agar isi tabel muat mulai halaman 1: padding
  baris `1.5pt 5pt`, kotak stempel `height: 40pt`, **label `nowrap`** (inline spt `docs/JSA new.docx`
  #9a) — kolom label 15% | value 25% | 3×TTD 20%. Thead padding `3pt`, `line-height 1.08`.
- Tabel analisa kolom **20/20/46/14** (Uraian | Bahaya | **Tindakan Pengendalian LEBAR** | Centang).
- Baris DATAR (bukan rowspan asli — lihat gotcha §7); efek sel-gabung ditiru via
  hapus border (`mrg-top/mid/bot`). Hanging indent per kolom (celah nomor→teks = jn−lebar nomor):
  `jc-langkah` pl19/jn14, `jc-bahaya` pl23/jn18, `jc-kendali` pl31/jn26; `.jn vertical-align: -2.1pt`.
  (Dulu 17/12, 20/15, 26/21 → nomor "N.M.K" nempel ke teks; dilebarkan agar selalu berjarak.)

**MESIN PAGINASI JSA — CHUNKING + ALIR NATURAL + 2-FASE (spt `docs/JSA new.docx`):**
DomPDF (a) tak bisa MEMECAH satu baris tabel antar halaman → baris tinggi di-bump utuh (halaman
separuh kosong) / meluber; (b) tak bisa mengulang header step di halaman lanjutan. Solusi di
`App\Services\JsaPrintLayout` + `DocumentController::renderJsaPaginated`:
1. **CHUNKING** (`build($analisa, $chunkFn)`): teks Bahaya & Kendali dipecah `chunkText()` jadi
   potongan **≤8 baris** (lebar area teks kolom, pt; font 8pt). Kendali diratakan lintas pengendalian
   (nomor "n.b.p" hanya di potongan pertama); Bahaya (risiko) mengalir di kolomnya (max/pad dgn kendali).
   `chunkFn` di controller pakai FontMetrics. Langkah TAK dipecah (pendek). **PENTING — 8, bukan 3:**
   chunk kecil = SANGAT banyak baris → DomPDF melambat SUPER-LINEAR (chunk 3 pernah bikin suite 56 MENIT).
   8 = kompromi: sel realistis (≤8 baris) → 1 potongan (tak nambah baris, cepat); hanya sel SANGAT
   panjang yg dipecah.
2. **ALIR NATURAL** (view TANPA `page-break-before`): baris mengalir & **MENGISI** halaman spt Word.
   `page-break-inside: avoid` DIHAPUS. (Forced-break dulu bikin halaman berhenti dini/separuh kosong.)
3. **2-FASE (ukur → tata)**: tiap baris ber-anchor 1pt tak-terlihat `[[Ri]]` (pola marker "APPROVED");
   `measureRowPageStarts()` baca baris mana mulai tiap halaman → `plan(rows, pageStarts)` menata
   `showLangkah` (ULANG teks Langkah di baris pertama tiap halaman lanjutan) + border `mrg-*` per
   (step/bahaya/pengendalian × halaman) → tepi tiap halaman TERTUTUP. **Loop cap 2** (perf);
   **fallback** 1-lintasan bila anchor tak terbaca (tak pernah lebih buruk).
- Nomor bahaya HANYA di baris awal bahaya (tak diulang; hanya Langkah yg diulang, spt referensi).
- SOP/IK/SP TETAP 1-lintasan. Preview layar pakai `flatten()` (tanpa chunking, alir biasa).
- **Performa**: JSA = 2-3 render (2-fase). Dok kecil ~3-4dtk, 12-step ~7dtk. Suite ~91dtk.
  Nice-to-have: **cache PDF per-versi** agar unduh ulang instan. JANGAN kecilkan chunkLines tanpa
  ukur ulang waktu (super-linear!).
- **KRITIS**: hasil = render FINAL SEGAR (output() SEKALI); output() ganda MERUSAK font (lihat §7).
- Sisa: (a) sel realistis rapi penuh spt Word; (b) sel SANGAT panjang celah dasar ≤8 baris (kompromi
  perf); (c) kata TUNGGAL tanpa spasi > kolom masih bisa meluber (garbage; JSA nyata tidak).

---

## 7. Pengetahuan DomPDF (didapat dari MENGUKUR stream PDF — jangan menebak lagi)

| Gotcha | Fakta terukur | Solusi |
|---|---|---|
| `<colgroup>` | **DIABAIKAN total** (kolom jadi rata) | Taruh `width` di sel baris pertama / `<th>` |
| `text-indent` negatif | Diterapkan **DUA KALI** (-20pt menggeser 40pt → nomor keluar kotak) | Pakai `margin-left` negatif pada span `inline-block` |
| Tabel bersarang | **Atomik** — tak bisa dipecah antar halaman (isi terpotong) | Tabel isi harus top-level |
| `rowspan` asli | **Merusak paginasi** (27 baris → 25 halaman, tiap halaman separuh kosong) | Baris DATAR + simulasi border |
| `word-break` | **DIABAIKAN** (FrameReflower/Text hanya baca `overflow_wrap`) | `overflow-wrap: anywhere` (satu-satunya yg penggal tengah kata) |
| Baseline `inline-block` | Nomor mengambang ~2–4pt di ATAS teks | `vertical-align` negatif terkalibrasi per ukuran font |
| Spesifisitas CSS | `table.kop td` (0,1,2) MENGALAHKAN `.kop-title` (0,1,0) → font-size tak terpakai (tetap 8pt) | Selektor `table.kop td.kop-title` |
| `position: fixed` | Berulang tiap halaman | `@page` top-margin harus memesan tingginya |
| `page_text()` | `{PAGE_NUM}`/`{PAGE_COUNT}` tanpa `enable_php` | Stamp setelah `render()` |
| **`output()` DUA KALI** | Panggil `output()` >1× pada satu Dompdf **MERUSAK subset font** → glyph kacau (jadi simbol aneh). Test berbasis-teks TAK menangkap (string literal tetap benar; yang rusak glyph). | Dompdf hasil = **output() sekali**. Mesin 2-fase pakai render PROBE terpisah utk ukur; render FINAL segar (`renderJsaPaginated` Fase 2). |
| `<thead>` sangat TINGGI | Berhenti berulang antar halaman (mis. kolom sempit + header panjang) | Jaga tinggi header wajar (mis. kolom centang JSA ≥14%) |

### Teknik verifikasi (dipakai di test & skrip scratchpad)
Render PDF via `renderPdfDocument()` → regex `stream…endstream` → `gzuncompress`/`gzinflate`
→ ambil literal `(...)` → buang `\x00` (DomPDF menulis font tertanam sebagai **UTF-16BE**)
→ parse operator `x y Td` untuk koordinat teks; garis border dari `re` (rect tipis) & `m…l`.
Ukuran font: `/Fn <size> Tf` ditulis DI DALAM segmen Td yang sama (bukan berurutan sebelumnya).

---

## 8. Form Peninjau vs Penyetuju

- **`resources/views/review/show.blade.php`** (PENINJAU) — analisa JSA nested LENGKAP.
  `$reviewTypes` memuat `jsa_analysis`; tiap Langkah Kerja, Bahaya & Risiko, Tindakan
  Pengendalian punya kotak catatan sendiri. `item_ref` = `L{li}`, `L{li}-B{bi}`, `L{li}-B{bi}-P{pi}`.
- **`resources/views/approvals/show.blade.php`** (PENYETUJU/PJO) — **RINGKAS, keputusan saja**:
  Rantai Dokumen + textarea alasan + tombol Setujui/Kembalikan. TIDAK ada form per-item
  (owner: "KENAPA DI RUBAH SIH YG SUDAH ADA TADI"). Reject wajib alasan → disimpan `[Penyetuju]`.
- Foto lampiran per dept di `storage/app/public/lampiran/{DEPT}/{JENIS}/` — bisa dilihat saat tinjau + dikomentari.

---

## 9. Testing (measurement-driven)

**`tests/Feature/PrintLayoutTest.php`** membaca stream PDF NYATA (bukan sekadar teks) untuk
mengunci tata letak. Test kunci:
- `jsa_kop_repeats_every_page_with_page_numbers`
- `jsa_kop_form_number_vs_job_number`
- `published_document_stamps_every_signature`
- `catatan_revisi_sheet_only_on_revised_documents`
- `no_text_crosses_bottom_margin` (setiap halaman y-terendah ≥ 55pt)
- `kop_title_font_is_enlarged` (JENIS **14pt**, JUDUL **12pt**, FORMULIR JSA 16pt)
- `activity_number_aligns_with_its_title` (selisih ≤ 0.6pt)
- `jsa_text_never_crosses_column_borders` (edge kolom 20/20/46/14; kata 112 char tanpa spasi)
- `sop_is_portrait_with_single_footer`
- `jsa_body_starts_on_first_page_and_header_repeats` (isi mulai hal.1, thead berulang; worst-case published)
- `jsa_step_header_repeats_on_continuation` (mesin 2-fase: Langkah diulang tiap halaman lanjutan)

**`tests/Unit/JsaPrintLayoutTest.php`** — logika `plan()` murni (tanpa render): header diulang
per segmen-halaman + border `mrg-*` menutup tepi halaman.

Status terakhir: **54 test hijau** (`php artisan test`).
Kalau mengubah geometri/font/lebar kolom → jalankan PrintLayoutTest + perbarui angka edge/koordinat.
**Catatan mesin 2-fase:** ubah CSS JSA (padding/line-height/kolom) TAK memecah mesin (ia MENGUKUR
posisi nyata, bukan konstanta) — tapi tetap jalankan test utk memastikan konvergensi.

---

## 10. Dashboard, Notifikasi, Audit

- **Dashboard**: sapaan personal (nama + selamat pagi/siang/malam WITA + selamat bekerja),
  kartu ringkasan angka per status (dari `documents`, filter hak akses), feed aktivitas (`audit_logs`).
- **Timeline** vertikal per dokumen di halaman detail (baca `audit_logs`). Menu Audit Log terpisah.
- **Notifikasi lonceng (in-app)**: Pembuat → rejected/approved. Peninjau → perlu ditinjau; yang
  diloloskan lalu ditolak approver. Approver → perlu disetujui.

---

## 11. AI (arsitektur siap, implementasi PASCA-MVP)

Interface `AiReviewerInterface`; `GeminiReviewer`; switchable via binding/config (key di `.env`).
Pola bantu peninjau: (1) RESUME dokumen, (2) HIGHLIGHT bagian salah/kurang tepat, (3) SARAN per
temuan, (4) poin terstruktur bila banyak. Saran AI diberi badge; reviewer adopsi/edit/tolak →
anotasi. Yang ke user = anotasi reviewer; asal-usul AI di audit log (`ai_generated`, `ai_adopted`).
AI TIDAK memblokir, TIDAK approve/reject sendiri. Konfirmasi izin kepatuhan data ke API eksternal dulu.

---

## 12. Perintah & Operasional

- **Renew data** (bukan migrate:fresh): `php artisan smartpro:renew [--force]` —
  truncate tabel operasional + users, bersihkan `storage/app/public/lampiran`,
  re-seed config + satu akun per role. (`app/Console/Commands/RenewData.php`.)
- **RTK** (dipakai owner di sesi baru): hook `PreToolUse → Bash → rtk hook claude`
  menulis ulang perintah Bash (mis. `git status` → `rtk git status`). **Hanya intercept Bash,
  bukan PowerShell.** Catatan: ada noise `$'\377\376export': command not found` dari `.bashrc`
  ber-encoding UTF-16 — abaikan, tidak memengaruhi hasil.

---

## 13. Akun Uji (dari seeder)
NRP contoh: `GL-0001` (Group Leader), `SH-0001` (Section Head), `PJO-0001` (PJO). Password seeder default.

---

## 14. Riwayat Commit Cetak (konteks)
`…v3 → v4 → v5 → v6` (geometri kop, font, stempel, kolom docx) →
`b0db00d` v8 (5.1 keluar kotak; form tinjau JSA nested; halaman PJO ringkas) →
`8ea7c6f` v9 (**font kop tak pernah besar** — bug spesifisitas CSS; sejajar nomor; JSA tembus kolom) →
`8b0d145` v10 (selaraskan layout dgn 4 template referensi: body 9pt/line-height 1.5, kolom kendali JSA lebar 46%).

---

## 15. Sisa Pekerjaan / Diketahui Belum Selesai
- ✅ **SELESAI — JSA "membawa konteks Langkah/Bahaya saat pindah halaman"**: diimplementasikan
  via **mesin paginasi 2-fase** (lihat §6 "MESIN PAGINASI JSA 2-FASE"). Header Langkah diulang
  di tiap halaman lanjutan, border tertutup per-halaman. Konvergen cepat + fallback aman.
- **BELUM — potong sel/kata TUNGGAL > 1 halaman** (Masalah 1/#4): DomPDF tak memecah satu sel →
  meluber. Hanya data garbage (kata tanpa spasi) yg kena; JSA nyata tidak. Butuh lapisan
  pemotongan teks di atas mesin 2-fase. Owner menunda (verifikasi context-carry di app dulu).
- **BELUM — #6 PIC SOP/IK/SP rata-tengah PER-HALAMAN** (bukan tengah seluruh kolom): butuh pola
  paginasi yg sama utk tabel `akt`. Rencana: fase berikut setelah JSA diverifikasi.
- Nice-to-have/pasca-MVP: cache PDF per-versi (unduh ulang instan), reassign Admin, diff versi,
  responsif mobile, implementasi AI, export Word/OCR, schema IK/SP/JSA lanjutan.

---

## 16. Nol-kan Dulu Sebelum Mengubah
1. Jalankan `php artisan test` — pastikan 54 hijau sebagai baseline.
2. Untuk perubahan cetak: gunakan skrip pengukuran stream (pola di §7) SEBELUM & SESUDAH.
3. Satu fase → berhenti → lapor singkat → tunggu review/commit owner.
