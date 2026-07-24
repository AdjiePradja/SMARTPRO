# CLAUDE.md — Aturan Proyek SmartPro Document Generator (PT PPA) — v3.1

> Dibaca otomatis Claude Code tiap sesi. Patuhi semua. Sumber kebenaran: `docs/PRD-SmartPro-v3.1.md`.
> File ini menang untuk teknis; PRD menang untuk produk. PRD v1/v2/v3.0 SUDAH DIGANTIKAN v3.1 — abaikan bila masih ada.

## 1. Konteks
Web internal PT Putra Perkasa Abadi (site Adaro) menggenerate dokumen mutu (SOP, SP, IK, JSA) untuk 7 departemen. Pengembang magang; kode direview senior IT. Utamakan kode STANDAR, jelas, terdokumentasi. Development SUDAH berjalan (~Fase 2) — MENYELARASKAN, bukan mulai nol.

## 2. Tech stack (WAJIB)
Laravel 12 (PHP 8.2+) · Blade + Bootstrap 5 + Alpine.js (JANGAN Vue/React/Inertia/Tailwind-SPA) · MySQL 8 via XAMPP · Auth NRP+password · RBAC spatie/laravel-permission · PDF barryvdh/laravel-dompdf · Icon Bootstrap Icons (JANGAN emoji). Timezone Asia/Makassar (WITA).

## 3. Konvensi kode (WAJIB)
- Controller & Model STANDAR Laravel (DocumentController, Document). DILARANG prefix C/M.
- Blade seragam {jenis}/{aksi}: resources/views/sop/index.blade.php, sop/create.blade.php, sop/edit.blade.php.
- Route seragam: sop.index, sop.create, sop.edit.
- Logika di Service class. Validasi Form Request. Otorisasi Policy/Gate. PSR-12.

## 4. Larangan KERAS (tanpa konfirmasi eksplisit saya)
- JANGAN migrate:fresh/migrate:refresh atau apa pun yang MENGHAPUS DATA. Tanya dulu.
- JANGAN commit .env. Pastikan .env di .gitignore.
- JANGAN hapus file/folder tanpa konfirmasi.
- JANGAN install package besar tanpa izin + alasan.
- JANGAN kerjakan banyak fase sekaligus. Satu fase → berhenti → tunggu review.
- JANGAN mengarang struktur dokumen independen — contoh belum ada, TUNGGU dari saya. (IK/JSA/SP SUDAH ada contohnya & sudah dibangun; lihat §17.)
- Saat integrasi aset referensi-visual: gunakan yang bisa dipakai; bila BENTROK dengan Bootstrap 5/Alpine, HENTIKAN & laporkan, jangan paksakan.

## 5. Cara kerja
Ikuti docs/INSTRUKSI-Merging-Berfase-v3.1.md fase demi fase. Tiap fase → BERHENTI → jelaskan singkat → tunggu review + commit Git. Belum jelas → TANYA. DB berubah → tunjukkan migration, tunggu approval.

## 6. Role, jabatan, alur, visibilitas (v3 — menggantikan alur lama)
- Jabatan: staff (label "Non-Staff", READ-ONLY), group_leader, section_head, departemen_head (= wewenang SH), pimpinan/PJO (TANPA departemen). Admin full akses (tercatat audit log).
- GL = SATU-SATUNYA PEMBUAT (posisi terendah alur; tidak meninjau, tidak Ajukan Revisi/nonaktifkan dokumen). PJO = PENYETUJU SAJA (tak pernah meninjau) + setujui akun baru.
- Matriks peninjau/penyetuju per jenis+dept: docs/aturan-alur-v2.md (SOP: tinjau SH/DH dept, approve PJO saja; IK/SP: SATU SH/DH dept MENINJAU SEKALIGUS MENYETUJUI — PJO tidak terlibat, pengesahan 2 baris "Dibuat Oleh" + "Ditinjau dan Disetujui Oleh"; JSA: SH/DH SHE & Plant lintas-dept). Dipusatkan di DocumentParticipantResolver.
- VISIBILITAS: GL "Status Dokumen" = HANYA dokumen buatannya sendiri (menu tunggal tanpa dropdown). Non-Staff = read-only se-departemen (dropdown per jenis). SH/DH/PJO TANPA menu Status Dokumen (pakai "Status Dokumen Staff"; PJO lintas 7 dept). Dokumen Berlaku + submenu Tidak Berlaku utk SH/DH/PJO/Admin & GL (GL read-only, tanpa hapus/nonaktif).

## 7. Arsitektur & status terkunci
- Schema-driven: schema JSON per jenis = sumber tunggal form/preview/PDF. Lihat docs/schema-sop.json.
- Partial bersama _kop/_footer/_pengesahan. Logo public/images/logo-ppa.png (BISA diklik -> home).
- Isi = value_json per section (document_contents). Jangan kolom tetap per jenis.
- Urutan bangun: SOP dulu (sampai PDF utuh) -> IK -> SP -> JSA terakhir.
- Pengisian 2 STEP. PREVIEW di PANEL KANAN (bukan tab baru), update saat klik Preview, 1:1, scrollable, di form input & edit.
- Status: draft, waiting_for_review, in_review, rejected, pending_approval, Berlaku, Sedang Direvisi, obsolete.
  - Edit/Hapus hanya draft. Withdraw hanya waiting_for_review.
- Batalkan Revisi (oleh GL/peninjau di Tinjau Dokumen -> Status Revisi): dokumen rejected -> kembali in_review (GL periksa ulang). Dokumen tipe B "Sedang Direvisi" -> kembali Berlaku.
- Dua revisi: Tipe A (ditolak, menu Dokumen Revisi pembuat). Tipe B (dokumen Berlaku, Ajukan Revisi oleh SH/DH/PJO/Admin — BUKAN GL; draft revisi dimiliki pembuat asli/GL; versi lama disimpan document_versions, otomatis OBSOLETE saat versi baru disahkan).
- Roll-over Edisi/Revisi (Tipe B): revisi 0..4; revisi berikutnya = Edisi+1 Revisi 0 (angka 5 tak pernah tampil). DocumentService::nextEditionRevision; deteksi "ini revisi" via versi lama sedang_direvisi bernomor sama (BUKAN no_revisi>0) + kolom revises_document_id.
- Draft revisi Tipe B: wizard punya langkah EKSTRA "Log Revisi" di akhir (Simpan/Kirim pindah ke sana); mengisi lembar CATATAN REVISI (NO|NO.REV|TANGGAL REV|HAL.|CATATAN, format docs/Lembar Revisi.docx) yang tercetak di halaman DEPAN PDF, barisnya terakumulasi lintas revisi.
- Reject approver -> feedback wajib -> balik ke GL -> rejected; peninjau yang meloloskan dinotifikasi.
- PDF: kop memuat "Halaman X dari Y" (di-stamp page_text DomPDF, koordinat terkalibrasi di DocumentController::stampPageNumbers). Kop JSA + Edisi, BERULANG tiap halaman via position:fixed (bukan thead — DomPDF memindahkan tabel utuh ke hal. 2); blok info+TTD JSA hanya hal. 1. Saat Berlaku SEMUA TTD bercap APPROVED (pakai published_at). Tata letak dikunci PrintLayoutTest (membaca stream PDF nyata).
- Lampiran foto per dept: storage/app/public/lampiran/{DEPT}/{JENIS}/. Bisa dilihat saat tinjau + bisa dikomentari.

## 8. Penomoran
PPA-ADRO-{JENIS}-{DEPT}-{NN}. SEMENTARA berlabel saat pembuatan/review; FINAL dikunci saat approved (tak bolong). Toggle manual (validasi unik).

## 9. Dashboard
Sapaan personal (nama + selamat pagi/siang/malam WITA + selamat bekerja) + kartu ringkasan angka per status (dari documents, filter hak akses) + feed aktivitas terbaru (dari audit_logs). Angka dari documents, feed dari audit_logs.

## 10. Timeline & Audit
Timeline vertikal per dokumen di halaman detail (baca audit_logs). Audit Log tetap menu terpisah.

## 11. Notifikasi lonceng (in-app)
Pembuat: rejected/approved. Peninjau: perlu ditinjau; yang diloloskan ditolak approver. Approver: perlu disetujui.

## 12. AI (arsitektur sekarang, implementasi pasca-MVP)
- Interface AiReviewerInterface; GeminiReviewer; switchable (Gemini->Claude/high-end) via binding/config. Input/output JSON terstandar. Key di .env.
- Pola bantu peninjau: (1) RESUME dokumen dulu, (2) HIGHLIGHT bagian salah/kurang tepat, (3) SARAN per temuan, (4) bila banyak -> poin-poin terstruktur.
- Saran AI ditandai jelas (badge). Reviewer adopsi/edit/tolak -> anotasi. Yang ke user = anotasi reviewer; asal-usul AI di audit log (ai_generated, ai_adopted). AI tak memblokir, tak approve/reject sendiri.
- Konfirmasi izin kepatuhan data ke API eksternal sebelum aktivasi.

## 13. UX & Tema
- Referensi tema di docs/referensi-visual/ (pages + assets, Soft UI-style). Tiru tabel, badge status, header bar, sidebar, card, spacing, tipografi. Gunakan aset yang bisa; bentrok -> HENTIKAN & lapor.
- Tema terang default + toggle dark. Palet selaras identitas PPA (logo merah) bila cocok.
- Standar UX tetap: lonceng, SweetAlert confirm sebelum aksi (Kirim/Hapus/Ajukan Revisi/Batalkan Revisi), dark theme, hover, transisi, skeleton/loading/empty state, breadcrumb, badge status berwarna, kolom Status+Action+Lihat PDF konsisten. Nol emoji (Bootstrap Icons). Logo klik->home.
- Feedback reviewer dua lapis: rangkuman di atas + anotasi di samping tiap poin.
- Foto lampiran bisa dilihat saat tinjau + dikomentari.
- Desktop dulu (mobile pasca-MVP).

## 14. Bug yang HARUS diperbaiki saat merging
- Timezone salah -> set Asia/Makassar (WITA) global; keterangan "tersimpan otomatis" tampilkan jam WITA benar.
- Tombol Kembali saat edit dokumen -> 403. Perbaiki otorisasi rute edit/kembali.

## 15. Keamanan
CSRF, hashing, rate-limit login, validasi semua input, Policy/Gate per peran, .env tidak masuk Git.

## 16. Nice-to-have / pasca-MVP (jangan kerjakan sampai MVP stabil)
Reassign Admin (T1); diff versi (T5); responsif mobile+wrapper; AI implementasi; export Word/OCR/PDF->Word/live-preview; dokumen independen (menunggu contoh). Schema SOP/IK/SP/JSA SUDAH selesai semua.

## 17. Referensi (docs/)
**BACA DULU tiap sesi baru: `docs/HANDOVER-SESI-BARU.md`** — konsolidasi seluruh konteks (aturan, peran/alur, arsitektur cetak PDF, gotcha DomPDF, kalibrasi, test).
PRD-SmartPro-v3.1.md · INSTRUKSI-Merging-Berfase-v3.1.md · schema-sop.json · SOP-contoh.pdf · screenshot form & review JSA · referensi-visual/ (pages+assets) · logo public/images/logo-ppa.png. IK/JSA/SP/independen: menyusul, jangan dikarang.
**Referensi VISUAL cetak (LOKAL/untracked, JANGAN commit, hanya acuan tampilan):** `template_sop.blade.php`, `template_ik.blade.php`, `template_sp.blade.php`, `template_jsa.blade.php`, `HANDOVER_PDF_SMARTPRO.md` (di root) — dari project lama Sistem_managemen_dokumen_prosedur. Juga: `docs/JSA new.docx`, `docs/Lembar Revisi.docx`, `docs/jsa_pic/`, `docs/lembar revisi/`, `docs/refrensi revisi/`, `docs/clauderev.md`.
