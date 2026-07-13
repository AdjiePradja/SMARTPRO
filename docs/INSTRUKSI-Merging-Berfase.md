# INSTRUKSI Merging Berfase — SmartPro (untuk Claude Code)

> **Situasi:** Development sudah berjalan (sekitar Fase 2). File baru (`PRD-SmartPro-v2.0.md`, `CLAUDE.md`, `schema-sop.json`, dokumen SOP contoh, screenshot, logo) baru ditambahkan.
> **Tujuan instruksi ini:** menyelaraskan kode yang SUDAH ADA dengan PRD v2.0 — **koreksi satu per satu, per fase, berhenti tiap fase untuk direview manusia.**
>
> **ATURAN EMAS:** Setiap fase → kerjakan → **BERHENTI** → laporkan apa yang dilakukan → tunggu saya review & commit Git → baru lanjut fase berikutnya. JANGAN lompat fase. JANGAN kerjakan beberapa fase sekaligus.

---

## PRA-SYARAT (lakukan sekali, sebelum Fase 0)
1. Pastikan file referensi ada di `docs/`: `PRD-SmartPro-v2.0.md`, `schema-sop.json`, dokumen SOP contoh (PDF), screenshot form Langkah 1 & 2, screenshot review JSA. Dan `CLAUDE.md` di root, logo di `public/images/logo-ppa`.
2. Commit Git keadaan sekarang: `git add -A && git commit -m "wip: state sebelum penyelarasan PRD v2"`. Ini titik balik pengaman.

---

## FASE 0 — AUDIT (JANGAN UBAH APA PUN)
**Tujuan:** memetakan kondisi kode saat ini vs PRD v2.0.
1. Baca `CLAUDE.md`, `docs/PRD-SmartPro-v2.0.md`, `docs/schema-sop.json`, dan lihat dokumen SOP contoh + screenshot.
2. Telusuri kode yang sudah ada. Laporkan dalam bentuk daftar:
   - (a) Apa saja yang SUDAH dibuat (migration, model, controller, view, route, auth, dll).
   - (b) Mana yang SUDAH sesuai PRD v2.0.
   - (c) Mana yang MENYIMPANG / bertentangan dengan PRD v2.0 (mis. login pakai email bukan NRP, penamaan, jumlah step, role/jabatan, dll).
   - (d) Apa yang BELUM ada tapi diminta PRD v2.0.
3. **JANGAN mengubah kode apa pun di fase ini.** Hanya laporan.
4. **BERHENTI.** Tunggu saya memutuskan urutan koreksi.

---

## FASE 1 — Penyelarasan Fondasi (auth, user, role, dept)
Kerjakan HANYA setelah saya setujui laporan Fase 0.
1. **Auth NRP:** ubah login jadi **NRP + password** (bukan email). Sesuaikan tabel users, form login, validasi.
2. **Users:** pastikan kolom `nrp`, `name`, `jabatan` (staff/group_leader/section_head/pimpinan), `department_id`, `status` (pending/active/rejected).
3. **RBAC (spatie):** role fungsional + Admin. Pemetaan jabatan→kewenangan sesuai PRD Bagian 2.
4. **Departments:** seed 7 dept (SHE, PLANT, HCGA, FWA[alias FALOG], ICTMD, PRODUKSI, ENGINEERING).
5. **Seed dummy:** 1 admin IT; **3 Group Leader dummy** (masing-masing punya NRP + dept berbeda) — data ini nanti muncul di dropdown peninjau/approver.
6. **Registrasi user dept + approval akun** (Admin/GL/Pimpinan approve). Buat akun GL/SH/Pimpinan oleh Admin.
7. **BERHENTI.** Laporkan perubahan + tunjukkan migration sebelum dijalankan. Test: login NRP, daftar→approve.

---

## FASE 2 — Penyelarasan Document Engine (SOP dulu)
1. **DB dokumen:** pastikan/betulkan tabel `document_types`(schema_json), `documents`, `document_authors`(is_primary), `document_contents`(value_json), `document_versions`, `attachments`, `audit_logs`, `reviews`, `review_annotations`, `approvals`. Soft delete pada documents. (Tunjukkan migration, tunggu approval, JANGAN migrate:fresh.)
2. **Seed** `document_types` SOP dari `docs/schema-sop.json`.
3. **Schema loader service** membaca schema_json.
4. **Form 2 STEP** (bukan 5) — gabungkan sesuai PRD Bagian 4:
   - Langkah 1: header + Tujuan + Ruang Lingkup + Referensi + Definisi.
   - Langkah 2: Aktivitas + Lampiran + pilih Peninjau & Approver (dropdown Nama+NRP+Dept).
   - Tombol tiap langkah: Kembali · Preview · Langkah Berikutnya.
   - Langkah terakhir: Kembali · Preview · Simpan · Kirim.
5. **Penomoran** `PPA-ADRO-SOP-{DEPT}-{NN}` auto + toggle manual.
6. **Autosave** draft ke document_contents.
7. **Lampiran foto** per dept: `storage/app/public/lampiran/{DEPT}/SOP/`.
8. **BERHENTI.** Test isi SOP 2 step + autosave + dropdown peninjau/approver menampilkan 3 GL dummy.

---

## FASE 3 — Preview & PDF (SOP)
1. **Partial bersama:** `_kop` (pakai `public/images/logo-ppa`), `_footer` (teks tidak terkendali), `_pengesahan` (hanya pembuat utama TTD, ikut format SOP contoh).
2. **Preview 1:1** panel/modal, scrollable, full-screen.
3. **PDF** SOP via DomPDF. **Bandingkan dengan dokumen SOP contoh** — format harus mendekati.
4. **Index pembuatan:** tabel dokumen dibuat — kolom No Dokumen, Judul, Jenis, Status, Action (Edit/Hapus **hanya draft**), Kirim (draft), Lihat PDF (tab baru).
5. **BERHENTI.** Bandingkan PDF hasil vs SOP contoh secara visual.

---

## FASE 4 — Alur Review, Revisi, Approval
1. **Kirim** → status in_review → masuk antrian **Tinjau Dokumen** peninjau (sesuai aturan jabatan pembuat).
2. **Review per-item:** peninjau highlight bagian + komentar per anotasi. Approve/Reject.
3. **Reject** → status `rejected` → muncul di **Dokumen Revisi** pembuat: kolom Status(Rejected), Feedback(rangkuman anotasi), Action(Revisi, Lihat PDF). "Revisi" buka form + tampilkan highlight & komentar. Anotasi lama tetap terlihat selama perbaikan.
4. **Lolos tinjau** → pending_approval → antrian approver.
5. **Approver reject** → feedback wajib → balik ke **GL** → jadi rejected; peninjau yang meloloskan dinotifikasi.
6. **Approver setuju** → published/**Berlaku** → masuk sub-menu departemen sesuai jenis dokumen.
7. **Menu "Status/Persetujuan Saya"** untuk GL/SH/Pimpinan.
8. **Audit log** mencatat tiap aksi (termasuk aksi admin).
9. **BERHENTI.** Test alur penuh: Staff buat→GL tinjau→reject→revisi→lolos→approver→Berlaku.

---

## FASE 5 — Revisi Tipe B (pembaruan dokumen Berlaku 0→1)
1. Di **sub-menu Departemen → jenis dokumen → index**: kolom Action **Ajukan Revisi** (untuk GL/Pimpinan/Admin) + Lihat PDF. Kolom Status: **Berlaku** / **Sedang Direvisi**.
2. "Ajukan Revisi" → dokumen lama jadi **Sedang Direvisi** (masih berlaku sementara), buat versi baru (No Revisi naik), review dari awal (anotasi lama tidak dibawa).
3. Setelah versi baru approved → versi lama `obsolete` (arsip di document_versions), versi baru Berlaku.
4. **BERHENTI.** Test 0→1 penuh + versi lama tersimpan.

---

## FASE 6 — Menu per Role, Notifikasi, Tema
1. **Struktur menu** tepat sesuai PRD Bagian 7 untuk Staff / GL / Section Head (tanpa menu pembuatan) / Pimpinan / Admin. Menu kondisional per role. Section Head hanya dept sendiri; Pimpinan semua dept.
2. **Notifikasi lonceng** per role (PRD Bagian 8).
3. **Tema terang default + toggle dark.** Nol emoji → Bootstrap Icons. Badge status berwarna, breadcrumb, empty/loading state.
4. **BERHENTI.** Test tiap role melihat menu & notif yang benar.

---

## FASE 7 — Dashboard, Filter, Pencarian
1. Dashboard grafik & statistik sesuai hak akses per role.
2. Filter + pencarian dokumen (nomor/judul/dept/status).
3. **BERHENTI.** Review akhir MVP.

---

## FASE 8 — AI Review Assist (setelah MVP stabil)
1. Interface `AiReviewerInterface` + implementasi (API disediakan saya). Summarize + highlight + saran.
2. Reviewer adopsi/custom/tolak → anotasi per-item.
3. 🟡 Konfirmasi izin kepatuhan data ke API eksternal sebelum kirim isi dokumen.
4. **BERHENTI.**

---

## MENYUSUL (jangan dikerjakan sampai contoh tersedia)
- Schema + blade cetak **IK, SP, JSA** (JSA terakhir, paling kompleks) — TUNGGU contoh dokumen dari saya.
- Dokumen independen (per-dept & lintas-dept) — TUNGGU daftar & contoh.
- Fitur lanjut: export Word, OCR, PDF→Word, live-preview-keystroke.

---

## Pengingat di tiap fase
- Berhenti & laporkan setelah tiap fase. Tunggu review + commit.
- Tunjukkan migration sebelum dijalankan. Jangan migrate:fresh.
- Jangan karang IK/JSA/SP/independen.
- Lihat dokumen SOP contoh + screenshot tiap kali menyentuh format/UX.
- Nol emoji, standar Laravel untuk controller/model, Blade/route seragam.
