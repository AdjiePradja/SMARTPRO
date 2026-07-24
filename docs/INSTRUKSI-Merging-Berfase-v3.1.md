# INSTRUKSI Merging Berfase — SmartPro — v3.1 (untuk Claude Code)

> **Situasi:** Development berjalan (~Fase 2). Referensi baru: PRD v3.1, CLAUDE.md v3.1, schema-sop.json, SOP contoh, screenshot, folder `docs/referensi-visual/` (pages+assets), logo.
> **Tujuan:** menyelaraskan kode existing dengan PRD v3.1 — koreksi per fase, berhenti tiap fase.
>
> **ATURAN EMAS:** Tiap fase → kerjakan → **BERHENTI** → laporkan → tunggu review + commit Git → lanjut. JANGAN lompat/gabung fase. Tunjukkan migration sebelum jalan. JANGAN migrate:fresh. JANGAN karang IK/JSA/SP/independen. Aset referensi bentrok → HENTIKAN & lapor.

---

## PRA-SYARAT (sekali)
1. `docs/`: PRD-SmartPro-v3.1.md, schema-sop.json, SOP-contoh.pdf, screenshot form 1&2, screenshot review JSA, folder referensi-visual/ (pages+assets). `CLAUDE.md` di root. Logo public/images/logo-ppa.png.
2. Hapus/pindahkan PRD & instruksi versi lama (v1/v2/v3.0) bila masih ada.
3. Commit: `git add -A && git commit -m "wip: sebelum penyelarasan PRD v3.1"`.

---

## FASE 0 — AUDIT (JANGAN UBAH APA PUN)
1. Baca CLAUDE.md, docs/PRD-SmartPro-v3.1.md, schema-sop.json; lihat SOP-contoh.pdf, screenshot, referensi-visual/.
2. Ringkas pemahaman: (a) tujuan, (b) larangan terpenting, (c) alur per jabatan pembuat, (d) aturan visibilitas per level jabatan, (e) dua jenis revisi + Batalkan Revisi, (f) urutan fase. Tunggu saya konfirmasi.
3. Telusuri kode existing. Laporkan: sudah ada apa; sesuai PRD v3.1; MENYIMPANG (login, penamaan, step, role/jabatan/visibilitas, status, timezone, bug 403); belum ada.
4. **JANGAN ubah kode. BERHENTI.**

---

## FASE 1 — Fondasi (auth NRP, user, role, dept, dummy, timezone)
1. Login NRP+password. Set **timezone Asia/Makassar (WITA)** global.
2. users: nrp, name, jabatan(staff/group_leader/section_head/pimpinan), department_id (**nullable** utk pimpinan), status(pending/active/rejected).
3. RBAC spatie + Admin; pemetaan jabatan→kewenangan + **aturan visibilitas per level jabatan** (PRD 3.3).
4. Seed 7 dept (SHE, PLANT, HCGA, FWA[alias FALOG], ICTMD, PRODUKSI, ENGINEERING).
5. Seed 1 Admin IT + **3 Group Leader dummy** (NRP + dept berbeda) → muncul di dropdown peninjau/approver.
6. Registrasi user dept + approval akun. Admin buat akun GL/SH/Pimpinan (Pimpinan tanpa dept).
7. **BERHENTI.** Tunjukkan migration dulu. Test login NRP, daftar→approve, timezone benar.

---

## FASE 2 — Document Engine + Preview Panel Kanan (SOP dulu)
1. DB dokumen: document_types(schema_json), documents(doc_number_temp, doc_number_final, status, no_revisi, current_revision_round, created_by; soft delete), document_authors(is_primary), document_contents(value_json), document_versions, attachments, attachment_comments, audit_logs, reviews, review_annotations(ai_generated, ai_adopted), approvals. (Tunjukkan migration, tunggu approval, JANGAN migrate:fresh.)
2. Seed document_types SOP dari docs/schema-sop.json.
3. Schema loader service.
4. Form **2 STEP** (PRD 6): Langkah 1 (header+Tujuan+Ruang Lingkup+Referensi+Definisi); Langkah 2 (Aktivitas+Lampiran+pilih Peninjau&Approver dropdown Nama+NRP+Dept). Tombol: Kembali·Preview·Berikutnya; terakhir Kembali·Preview·Simpan·Kirim.
5. **PREVIEW PANEL KANAN:** klik Preview → panel kanan update, dokumen 1:1, scrollable. BUKAN tab baru. Di form input & edit.
6. Penomoran **sementara** berlabel + toggle manual. Final belum diberikan di fase ini.
7. Autosave draft. Keterangan "tersimpan otomatis" pakai jam WITA.
8. Lampiran foto: storage/app/public/lampiran/{DEPT}/SOP/, bisa dikomentari (attachment_comments).
9. **Bug fix:** tombol Kembali saat edit tidak boleh 403 — perbaiki otorisasi.
10. **BERHENTI.** Test isi 2 step + preview kanan + autosave + dropdown 3 GL dummy + tombol kembali tidak 403.

---

## FASE 3 — Preview & PDF (SOP)
1. Partial bersama: _kop (logo, BISA diklik→home saat di layout app), _footer (teks tidak terkendali), _pengesahan (pembuat utama TTD, ikut SOP contoh).
2. PDF SOP via DomPDF. **Bandingkan dengan SOP-contoh.pdf.**
3. Index pembuatan: kolom No Dokumen(sementara), Judul, Jenis, Status, Action(Edit/Hapus hanya draft), Kirim(draft)/Tarik(waiting_for_review), Lihat PDF(tab baru). Dialog konfirmasi (SweetAlert-style) sebelum Kirim/Hapus.
4. **BERHENTI.** Bandingkan PDF vs contoh.

---

## FASE 4 — Review, Withdraw, Revisi, Batalkan Revisi, Approval
1. Kirim → `waiting_for_review` (pembuat bisa Tarik → draft).
2. Reviewer buka → `in_review`. Masuk antrian Tinjau Dokumen (sesuai jabatan pembuat).
3. Review per-item: highlight + komentar per anotasi; **foto lampiran bisa dilihat + dikomentari**. Approve/Reject. **Feedback dua lapis:** rangkuman di atas + anotasi samping tiap poin.
4. Reject → `rejected` → menu **Dokumen Revisi** pembuat (Status Rejected, Feedback rangkuman, Action Revisi/Lihat PDF). "Revisi" buka form + tampilkan highlight & komentar; anotasi lama tetap terlihat.
5. **Peninjau memantau di Tinjau Dokumen → Status Revisi** (kolom tahap pengerjaan, Lihat, **Batalkan Revisi**). Batalkan Revisi pada rejected → dokumen kembali **in_review** (GL periksa ulang).
6. Lolos → `pending_approval` → antrian approver.
7. Approver reject → feedback wajib → balik ke GL → rejected; peninjau yang meloloskan dinotifikasi.
8. Approver setuju → **Berlaku** + nomor final dikunci → sub-menu departemen. **PDF pengesahan: kolom Ditinjau Oleh DAN Disetujui Oleh dua-duanya Approved.**
9. Menu Status/Persetujuan Saya. Audit log tiap aksi.
10. **BERHENTI.** Test alur penuh + Batalkan Revisi + pengesahan dua kolom Approved.

---

## FASE 5 — Revisi Tipe B (0→1) + Batalkan
1. Sub-menu Departemen → jenis → index: Action **Ajukan Revisi** (GL/Pimpinan/Admin) + Lihat PDF. Status Berlaku/Sedang Direvisi.
2. Ajukan Revisi → dokumen lama Sedang Direvisi (Berlaku sementara); versi baru (No Revisi naik), review dari awal. Dialog konfirmasi.
3. **Batalkan** dokumen tipe B "Sedang Direvisi" → kembali Berlaku (versi lama tetap aktif).
4. Versi baru approved → versi lama obsolete (arsip), versi baru Berlaku.
5. **BERHENTI.** Test 0→1, batalkan, arsip versi lama.

---

## FASE 6 — Menu per Role, Visibilitas, Notifikasi, Dashboard, Timeline, Tema
1. Struktur menu PRD 7 (Staff/GL/SH tanpa pembuatan/Pimpinan tanpa dept/Admin), kondisional per role. **Informasi Akun di semua role.**
2. **Visibilitas per level jabatan:** "Status Dokumen" (sesama level, dept sama). GL "Status Dokumen Staff" read-only deptnya. SH "Status Dokumen Staff" read-only dept sendiri.
3. Notifikasi lonceng per role (PRD 10).
4. **Dashboard** (PRD 8): sapaan personal (nama+selamat pagi/siang/malam WITA+selamat bekerja) + kartu ringkasan (dari documents) + feed aktivitas (dari audit_logs).
5. **Halaman detail dokumen + timeline vertikal** (audit_logs). Audit Log menu terpisah.
6. **Tema Soft UI-style** dari docs/referensi-visual/: tiru tabel, badge status, header bar, sidebar, card, spacing, tipografi. Gunakan aset yang bisa; **bentrok → HENTIKAN & lapor.** Tema terang default + toggle dark. Palet selaras PPA. Nol emoji → Bootstrap Icons. Logo klik→home. SweetAlert-style confirm.
7. **BERHENTI.** Test tiap role: menu, visibilitas, notif, dashboard, timeline, tema, dark toggle.

---

## FASE 7 — Filter, Pencarian, Review Akhir MVP
1. Filter + pencarian dokumen (nomor/judul/dept/status).
2. Review akhir MVP.
3. **BERHENTI.**

---

## FASE 8 — AI Review Assist (setelah MVP stabil)
1. Interface AiReviewerInterface + GeminiReviewer (API disediakan saya). Switchable via binding/config. Input/output JSON terstandar.
2. **Pola bantu peninjau:** RESUME dokumen → HIGHLIGHT bagian salah → SARAN per temuan → bila banyak, poin-poin terstruktur.
3. Saran AI badge jelas. Reviewer adopsi/edit/tolak → anotasi. Yang ke user = anotasi reviewer; asal-usul AI di audit log. AI tak memblokir, tak approve/reject sendiri.
4. Konfirmasi izin kepatuhan data ke API eksternal.
5. **BERHENTI.**

---

## MENYUSUL / NICE-TO-HAVE (jangan kerjakan sampai diminta)
Schema+blade cetak IK, SP, JSA (JSA terakhir) — tunggu contoh. Dokumen independen — tunggu contoh (menu disiapkan, isi kosong). Reassign Admin (T1). Diff versi (T5). Responsif mobile+wrapper. Export Word, OCR, PDF→Word, live-preview-keystroke.

---

## Pengingat tiap fase
Berhenti & laporkan, tunggu review+commit. Migration ditunjukkan sebelum jalan; jangan migrate:fresh. Jangan karang IK/JSA/SP/independen. Lihat SOP-contoh.pdf + screenshot + referensi-visual tiap sentuh format/UX/tema. Aset bentrok → hentikan. Nol emoji; controller/model standar Laravel; Blade/route seragam; timezone WITA.
