# JSA — Konfigurasi Cetak PDF (Backup / Titik Pulih)

> **Status:** BEKERJA BAIK — **v12 per 2026-07-24**: (1) kop di **halaman lembar revisi + halaman pertama body** (tanpa revisi → hal. 1 saja); (2) kop & baris tabel **dirampingkan ~20%**; (3) **ISI HALAMAN PENUH** — halaman terisi sampai bawah, grup panjang dipotong per baris pengendalian, Langkah/Bahaya diulang di atas halaman lanjutan (ganti mesin probe-datar v10 yang under-fill). Indentasi baris lanjutan sejajar (v11). Proporsi rowspan/rata-tengah/cover benar (v10).
> **Tujuan file ini:** rekam-jejak konfigurasi yang sudah benar agar bisa dipulihkan bila revisi berikutnya merusak sesuatu. Bila mengubah JSA, baca ini dulu; bila rusak, kembalikan ke nilai/logika di sini.
> Test penjaga: `tests/Feature/PrintLayoutTest.php` + `tests/Unit/JsaPrintLayoutTest.php` (14 test, membaca stream PDF nyata). Jalankan sebelum & sesudah perubahan.

## 1. Berkas yang terlibat
| Berkas | Peran |
|---|---|
| `app/Services/JsaPrintLayout.php` | Kelas murni (tanpa render): `flatten()` + `plan()` + `spanCount()`. Menyusun baris & rowspan per halaman. Mudah diuji. |
| `resources/views/documents/print/render-jsa.blade.php` | Template cetak JSA (CSS + tabel analisa rowspan + blok info/TTD). |
| `resources/views/documents/print/_kop_jsa.blade.php` | Kop atas (Formulir SHE), berulang tiap halaman via `position:fixed`. |
| `app/Http/Controllers/DocumentController.php` | Orkestrasi paginasi 2-fase: `renderJsaPaginated()`, `renderJsaOnce()`, `measureRowPageStarts()`, `stampPageNumbers()`. |

## 2. Model tabel: ROWSPAN asli (seperti `docs/JSA new.docx`)
Satu **BARIS per pengendalian**. Sel digabung dengan `rowspan`:
- **Uraian Langkah** — di-`rowspan` melintasi SELURUH bahaya + pengendalian miliknya.
- **Bahaya dan Risiko** — di-`rowspan` melintasi SELURUH pengendalian miliknya.

Efek (semua sudah benar di versi ini):
1. Uraian & Bahaya **rata tengah** atas-bawah pada tinggi gabungannya (`vertical-align: middle`) → tak ada celah kosong.
2. Bila sel Bahaya lebih tinggi dari total pengendaliannya, DomPDF membagi rata tinggi lebih ke baris-baris pengendalian → `1.1.1 / 1.1.2 / 1.1.3` **proporsional**.
3. Bahaya **menguasai** semua pengendalian di bawahnya (cover benar).
4. Penomoran hirarkis: langkah `1.`, bahaya `1.1`, kendali `1.1.1`.

## 3. Masalah DomPDF & solusinya (JANGAN dihapus)
DomPDF: (a) **tak bisa memecah SATU baris `<tr>` antar halaman**; (b) sel `rowspan` yang **melintasi batas halaman rusak** (tumpang-tindih / hilang).

**Solusi paginasi (v12 — ISI HALAMAN PENUH)** (`renderJsaPaginated()`):

Fakta DomPDF (terbukti dari eksperimen): dgn aliran alami, DomPDF **mengisi halaman sampai bawah & MEMECAH grup rowspan sendiri** — TAPI sel Langkah/Bahaya jadi **KOSONG** di halaman lanjutan (rowspan tak diulang). Maka strategi:
1. **FASE A — KANDIDAT** (aliran alami, `forceBreaks = false`): render rowspan NYATA ber-scope di `$starts`, tanpa page-break paksa → DomPDF memotong secara ALAMI (mengisi penuh). `measureRowPageStarts()` (penanda `'Uraian'`, anchor `[[Ri]]`) membaca indeks baris awal-halaman. Diiterasi (scope menggeser titik → bisa **berosilasi ±1 baris**) → kumpulkan semua kandidat.
2. **FASE B — PILIH**: kandidat dgn potongan **paling sedikit** (halaman paling sedikit) yang **STABIL saat DIPAKSA** — render `forceBreaks = true` di kandidat, lalu ukur; bila `forced-measured === kandidat` berarti scope **persis** cocok (tak meluber, tak ada sel kosong/ganda). Kandidat "terlalu rakus" (satu baris meluber saat dipaksa) ditolak; yg lebih banyak potongan hampir selalu stabil → selalu ketemu.
3. **FINAL** = render `forceBreaks = true` di `$starts` terpilih → `page-break-before: always` memaksa DomPDF memotong PERSIS di titik ber-scope → Langkah/Bahaya yg berlanjut **diulang** (rowspan baru per halaman), border tertutup, **halaman terisi penuh**. `output()` dipanggil SEKALI oleh pemanggil → subset font utuh.

Fallback: bila anchor tak terbaca / tak ada kandidat stabil → render 1-lintasan biasa.
⚠️ JANGAN kembalikan ke "probe datar" v10: layout datar over-estimasi tinggi → halaman UNDER-FILL (Langkah 2 dilempar utuh, whitespace besar). Titik-potong HARUS diukur dari aliran alami rowspan nyata.

## 4. `JsaPrintLayout` — kontrak
- **`flatten(array $analisa): array`** — ratakan analisa nested → baris datar, 1 per pengendalian. Setiap baris menyimpan `stepIdx/bahayaIdx/pengIdx`, `stepNo` (`"N."`), `bahayaNo` (`"N.M"`), `kendaliNo` (`"N.M.K"` atau `""`), plus teksnya. Bahaya tanpa pengendalian → tetap 1 baris (`pengList = ['']`) agar bahaya tetap tampil.
- **`plan(array $rows, array $pageStarts = []): array`** — set `pageNo`/`pageBreakBefore` dari `$pageStarts`, lalu (di-scope per halaman) hitung `showLangkah/showBahaya` & `stepRowspan/bahayaRowspan`. Tanpa `$pageStarts` = satu-lintasan (dipakai untuk probe & fallback/preview layar).
- **`spanCount()`** — hitung baris berturut yang masih memenuhi predikat (step/bahaya sama & halaman sama).

## 5. Konstanta CSS terkalibrasi (`render-jsa.blade.php`)
Nilai berikut hasil kalibrasi terhadap stream PDF nyata — **ubah hati-hati, jalankan test.**

| Item | Nilai | Catatan |
|---|---|---|
| `@page` margin | `20pt 8pt 57pt 8pt` | kop tak dipesan di margin → atas 139pt→20pt. Kiri/kanan 8pt ≈ full-bleed; bawah 57pt ≈ 2cm. |
| `.kop-block` | `margin-bottom: 0` (STATIC) | **v12**: kop mengalir biasa, di-include DUA KALI di body — atas lembar revisi (bila ada) + atas blok info/TTD → muncul di halaman revisi & halaman pertama body. Bukan `position:fixed`. |
| Kop ramping (v12) | logo `52pt`, `td padding 0.4pt 5pt`, `font 7.5pt` `line-height 1.1`, judul `13pt` | ~20% lebih pendek dari v11 (logo 58/pad 1pt) & jauh dari v10 (logo 92/16pt). |
| Baris tabel analisa (v12) | `td padding 1.5pt 5pt`, `line-height 1.25` | ramping ~20%: ±13pt/baris (≈0.46cm) dari ±16.4pt (padding 3pt). |
| Kolom tabel analisa | `20% / 20% / 46% / 14%` | Uraian / Bahaya / **Pengendalian (prioritas lebar)** / kolom centang. Rasio 46/14 dipilih agar header `<thead>` tetap **berulang** tiap halaman (kolom centang 10% membuat header terlalu tinggi → DomPDF berhenti mengulang). |
| Kop kolom | `12.3% / 63.2% / 9% / 15.5%` | ikon 3.5cm / judul 18cm / meta. Lebar **wajib** di sel baris pertama (DomPDF abaikan `<colgroup>`). |
| `td.kop-title` font | `13pt` (v11; dulu 16pt) | **harus** pakai selector `table.kop td.kop-title` — `.kop-title` polos kalah spesifisitas → font tak terpakai. |
| Hanging indent `.jn` | langkah `width:14pt / ml:-14pt`, bahaya `18/-18`, kendali `26/-26`; padding-left sel `19/23/31pt` | nomor **dalam** kotak (5pt dari border), teks & baris lanjutan mulai di padding-left. `vertical-align: -2.1pt` menyejajarkan nomor dgn teks. |
| Anchor `[[Ri]]` | di **AKHIR** sel kendali | **v11 — WAJIB.** Bila di DEPAN, lebarnya (±2.9pt) menggeser HANYA baris pertama ke kanan (372.7 vs 369.8) → baris ke-2 dst. tak sejajar. Dikunci `PrintLayoutTest::test_wrapped_lines_align_with_first_line`. |
| Pemenggalan kata | `overflow-wrap: anywhere` | DomPDF **abaikan** `word-break`; hanya `anywhere` yang memenggal kata super-panjang tanpa spasi. |
| `page-break-inside: avoid` pd `tbody tr` | — | baris sangat tinggi pindah **utuh** ke halaman berikut alih-alih meluber margin bawah. |

## 6. Kop & stamp halaman
- Kop `_kop_jsa.blade.php`: No. Dokumen kop = **nomor Formulir SHE** hardcode `PPA-ADRO-F-SHE-03B` (bukan nomor dokumen kita — itu di baris "No. Pekerjaan/JSA" pada blok info). Revisi/Edisi/Tgl Efektif otomatis dari dokumen.
- **v12: kop di halaman lembar revisi + halaman pertama body** (dua `@include('_kop_jsa')` static di body; `_catatan_revisi` punya `page-break-after: always` → body mulai halaman baru). Tanpa revisi → 1 kop (hal. 1). Blok info+TTD & footer mengalir → hanya di halaman body pertama / terakhir. Halaman analisa lanjutan cukup **header tabel** (thead).
- Nomor "X dari Y" di-**stamp di tiap halaman berkop** lewat **`page_script()`** (bukan `page_text()` yg menulis SEMUA halaman): `stampPageNumbers($dompdf,'landscape',$kopPages)` dgn `$kopPages = $hasRevisi ? [1,2] : [1]`; `if (! in_array($pageNumber,$kopPages)) return;` lalu `text(711.0, 59.0, "$pageNumber dari $count", 7.5pt)`. Koordinat = sel Halaman kop ramping — **berubah bila tinggi kop diubah**. (Asumsi lembar revisi = 1 halaman.)
- ⚠️ **Penanda halaman saat pengukuran = `'Uraian'` (header tabel, berulang via thead), BUKAN `'FORMULIR'`** (kop hanya hal. 1-2). Memakai `'FORMULIR'` → halaman analisa lanjutan tak terdeteksi → pengulangan Langkah/Bahaya **gagal diam-diam**.
- Saat dokumen sudah `published_at` != null → **semua** sel TTD bercap APPROVED (`public/images/approve-stamp.png`).

## 7. Kerabat: tabel AKTIVITAS SOP/SP/IK
`app/Services/ActivityPrintLayout.php` + `DocumentController::renderStandardPaginated()` memperbaiki bug border SOP/SP/IK: saat satu aktivitas terpotong antar halaman, sel PIC rowspan rusak (PIC hilang) & border tabel **menggantung**. Rowspan PIC & kelas border (`mrg-top/mid/bot`) di-scope PER HALAMAN → tepi selalu tertutup. Dikunci `tests/Unit/ActivityPrintLayoutTest.php`. Penanda halaman `'Dokumen'` (kop SOP/SP/IK MASIH berulang tiap halaman via `position:fixed`), anchor `[[Ai]]` di AKHIR sel.
> **Catatan:** SOP/SP/IK memakai **probe-datar + forced-break** (bukan mesin ISI-PENUH v12 milik JSA). Fill JSA (#4) khusus JSA. Bila kelak SOP/SP/IK juga perlu isi-penuh, tiru pola FASE A/B JSA.

> File ini adalah titik-pulih. Bila mengubah JSA lagi, perbarui nilai di §5/§6 di sini setelahnya.
