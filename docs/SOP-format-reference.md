# Referensi Format Cetak SOP (PPA-ADRO-SOP-ICTMD-01)

> Sumber: dokumen contoh asli PT PPA (site Adaro). Format cetak SOP **HARUS sama persis** dengan ini.
> Dipakai untuk membangun partial bersama `_kop`, `_footer`, `_pengesahan` (D3) + blade cetak SOP → DomPDF.

## Aset
- **Logo PPA:** `public/images/logo-ppa.png` (PNG, background transparan/putih). Dirujuk via `public_path('images/logo-ppa.png')` agar terbaca DomPDF.

## Kop / Header (muncul di SETIAP halaman)
Tabel berbingkai, 3 kolom:
| Logo (kiri) | Judul (tengah) | Metadata (kanan) |
|---|---|---|
| Logo PPA | **STANDARD OPERATING PROCEDURE** (baris atas, bold, center) | `No. Dokumen: {doc_number}` |
| | (garis pemisah) | `No. Revisi: {no_revisi}` |
| | **{JUDUL DOKUMEN}** (bold, center, uppercase) | `Edisi: {edisi}` |
| | | `Tgl. Terbit: {published_at or -}` |
| | | `Tgl. Revisi: {tgl_revisi or -}` |

- Kolom kanan: 5 baris, tiap baris bergaris. Nilai kosong ditampilkan `-`.
- Font kop kecil (~9pt), border tipis hitam.

## Body Sections
Tiap section punya **header bar abu-abu** (mis. `I. TUJUAN`) lalu isi.

1. **I. TUJUAN** — daftar dua kolom: `1.1`, `1.2`, … (nomor di kolom kiri sempit, teks di kanan).
2. **II. RUANG LINGKUP** — `2.1`, `2.2`, …
3. **III. REFERENSI** — `3.1`, `3.2`, …
4. **IV. DEFINISI** — `4.1`, `4.2`, …
5. **V. AKTIVITAS DAN TANGGUNG JAWAB** — **TABEL** 2 kolom:
   - Kolom `AKTIVITAS` (lebar): nomor `5.1` + **sub-judul (bold)** + deskripsi (bisa multi-baris/bullet).
   - Kolom `PIC` (sempit, center): mis. `ICT`.
6. **VI. LAMPIRAN** — daftar bernomor `1.`, dengan sub-huruf `a.`, `b.`, … (MVP: judul + keterangan per item).

## Halaman Pengesahan
Header bar abu-abu `HALAMAN PENGESAHAN`, lalu tabel:
| NAMA | JABATAN | TANGGAL | PENGESAHAN |
|---|---|---|---|
| Dibuat Oleh: **{nama}** | {jabatan} | {tanggal} | (stempel) |
| Ditinjau Oleh: **{nama}** | {jabatan} | {tanggal} | |
| Disetujui Oleh: **{nama}** | {jabatan} | {tanggal} | |

- Saat status `published`: tampilkan **stempel APPROVED** hijau miring di kolom PENGESAHAN.

## Footer (setiap halaman)
Teks biru italic, kecil:
> *Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.*

## Catatan
- Ukuran kertas A4, margin standar.
- Kop + footer berulang di tiap halaman (DomPDF: pakai `<div>` fixed atau tabel header berulang).
