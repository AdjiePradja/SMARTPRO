# Aturan Alur Peninjauan & Persetujuan (v2) — SmartPro

> Sumber: keputusan pemilik produk (chat revisi). Dipakai untuk Fase B (logika
> kandidat dropdown peninjau/penyetuju + validasi). SHE & Plant adalah dept khusus
> untuk JSA.

## Peran (Fase A)
- **Non-Staff** (dulu "staff"): read-only, TIDAK bisa membuat dokumen.
- **Group Leader (GL)**: satu-satunya PEMBUAT. Tidak meninjau.
- **Section Head (SH)** & **Departemen Head (DH)**: meninjau &/atau menyetujui (sesuai matriks).
- **Pimpinan (PJO)**: **PENYETUJU SAJA — TIDAK PERNAH meninjau.** Bisa menyetujui
  SEMUA dokumen. Melihat 7 departemen; menyetujui akun Non-Staff baru.

## Matriks PENINJAU (1 orang, dipilih GL) — PJO tidak pernah masuk
| Jenis | Dept dokumen | Kandidat peninjau |
|---|---|---|
| SOP | mana pun | SH/DH **dept sendiri** |
| IK  | mana pun | SH/DH **dept sendiri** |
| SP  | mana pun | SH/DH **dept sendiri** |
| JSA | **selain** SHE & Plant | SH/DH dept sendiri **+** SH/DH **SHE** **+** SH/DH **Plant** |
| JSA | SHE atau Plant | SH/DH **SHE** & **Plant** |

## Matriks PENYETUJU (approver) — PJO selalu boleh
| Jenis | Dept dokumen | Kandidat penyetuju |
|---|---|---|
| **SOP** | mana pun | **PJO saja** |
| IK  | mana pun | PJO **atau** SH/DH dept sendiri |
| SP  | mana pun | PJO **atau** SH/DH dept sendiri |
| JSA | selain SHE & Plant | PJO **atau** SH/DH **SHE** **atau** SH/DH **Plant** |
| JSA | SHE atau Plant | PJO **atau** SH/DH **SHE** **atau** SH/DH **Plant** |

> Catatan JSA: penyetuju JSA **tidak** termasuk SH/DH dept sendiri (kecuali dept-nya
> memang SHE/Plant) — mereka berperan sebagai **peninjau**. Persetujuan JSA ada di
> PJO atau SH/DH SHE & Plant.

## Catatan penting
- **PJO = penyetuju saja.** Tidak pernah jadi kandidat peninjau untuk jenis apa pun.
- **SOP**: approver WAJIB PJO (peninjau SH/DH otomatis ≠ approver → tetap terpisah).
- **IK, SP, JSA**: approver BOLEH orang/peran yang SAMA dengan peninjau
  (aturan "peninjau ≠ penyetuju" DILONGGARKAN untuk jenis ini).
- **GL BUKAN peninjau** — GL hanya PEMBUAT (posisi terendah alur). Peninjau = SH/DH.
- JSA selalu bisa dimulai (dibuat) oleh GL dept terkait.
