<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * Seeds document type schemas (D1). SOP is built first as the reference
 * pattern (D4); IK, SP and JSA follow once sample documents are available.
 *
 * The schema is the single source of truth: one engine renders the form,
 * the preview, and the PDF from it — so they cannot drift apart.
 */
class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // SOP: 6 bab; alur peninjau (SH/DH) → penyetuju (PJO), pengesahan 3 baris.
        DocumentType::updateOrCreate(
            ['code' => 'SOP'],
            [
                'name' => 'Standard Operating Procedure', 'class' => 'inti',
                'scope' => 'all_departments', 'is_active' => true,
                'schema_json' => $this->standardSchema('SOP', 'STANDARD OPERATING PROCEDURE', 'sop'),
            ],
        );

        // SP (Standar Produksi): layout 6 bab identik SOP; alur SATU SH/DH yang
        // meninjau SEKALIGUS menyetujui (pengesahan 2 baris) — referensi
        // docs/PPA-ADRO-SP-ICTMD-01.docx.
        DocumentType::updateOrCreate(
            ['code' => 'SP'],
            [
                'name' => 'Standar Produksi', 'class' => 'inti',
                'scope' => 'all_departments', 'is_active' => true,
                'schema_json' => $this->standardSchema('SP', 'STANDARD PRODUKSI', 'dual'),
            ],
        );

        // IK (Instruksi Kerja): HANYA tabel Aktivitas & Tanggung Jawab → langsung
        // pengesahan; alur satu SH/DH (dual) — referensi docs/Instruksi-kerja.docx.
        DocumentType::updateOrCreate(
            ['code' => 'IK'],
            [
                'name' => 'Instruksi Kerja', 'class' => 'inti',
                'scope' => 'all_departments', 'is_active' => true,
                'schema_json' => $this->ikSchema(),
            ],
        );

        // JSA — Formulir SHE landscape tersendiri (docs/JSA.docx). Alur review/
        // approval mengikuti aturan SOP (jabatan-based).
        DocumentType::updateOrCreate(
            ['code' => 'JSA'],
            [
                'name' => 'Job Safety Analysis',
                'class' => 'inti',
                'scope' => 'all_departments',
                'is_active' => true,
                'schema_json' => $this->jsaSchema(),
            ],
        );
    }

    /**
     * Schema JSA (Formulir Job Safety Analysis) — landscape, berbeda dari SOP.
     * Struktur analisa: Langkah Kerja → Bahaya & Risiko → Tindakan Pengendalian
     * (nested 3 tingkat). Alur review/approval sama dengan SOP (jabatan-based).
     */
    private function jsaSchema(): array
    {
        return [
            'doc_type' => 'JSA',
            'doc_type_label' => 'FORMULIR JOB SAFETY ANALYSIS',
            'orientation' => 'landscape',
            'header' => '_kop_jsa',
            'footer' => '_footer',
            'print_view' => 'documents.print.render-jsa',
            'footer_text' => 'Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.',
            'approval_page' => '_pengesahan',
            'steps' => [
                [
                    'step' => 1,
                    'title' => 'Informasi Umum',
                    'sections' => [
                        ['key' => 'lokasi_kerja', 'label' => 'Lokasi Kerja', 'type' => 'text', 'placeholder' => 'mis. View Point'],
                        ['key' => 'apd', 'label' => 'APD yang digunakan', 'type' => 'rich_list', 'min_items' => 1, 'placeholder' => 'mis. Helm safety, Body harness, Sarung tangan...'],
                        ['key' => 'tools', 'label' => 'Peralatan yang digunakan', 'type' => 'rich_list', 'min_items' => 1, 'placeholder' => 'mis. Obeng, Bor, Tang...'],
                        // No. Dokumen & Revisi pada kop TIDAK diisi manual: keduanya
                        // otomatis (penomoran + no_revisi) spt SOP/IK/SP. Hanya Tgl
                        // Efektif yang diisi pembuat, tanpa isi default.
                        // Tanggal WAJIB widget tanggal (bukan diketik).
                        ['key' => 'form_tgl_efektif', 'label' => 'Tgl. Efektif (kop)', 'type' => 'date'],
                        [
                            'key' => 'peninjau', 'label' => 'Ditinjau Oleh (DH/SH)',
                            'type' => 'user_picker', 'role_filter' => ['group_leader', 'section_head'], 'required' => true,
                        ],
                        [
                            'key' => 'penyetuju', 'label' => 'Disetujui Oleh (PJO)',
                            'type' => 'user_picker', 'role_filter' => ['pimpinan'], 'required' => true,
                        ],
                    ],
                ],
                [
                    'step' => 2,
                    'title' => 'Analisa Bahaya',
                    'sections' => [
                        [
                            'key' => 'analisa', 'label' => 'Analisa Bahaya', 'type' => 'jsa_analysis',
                            'help' => 'Masukkan tahapan pekerjaan, potensi bahaya yang mungkin timbul, dan langkah pengendaliannya.',
                        ],
                    ],
                ],
            ],
            'approval_page_layout' => [
                'columns' => ['Nama', 'Jabatan', 'Tanggal', 'Pengesahan'],
                'rows' => [
                    ['role_label' => 'Dibuat Oleh', 'role' => 'pembuat'],
                    ['role_label' => 'Ditinjau Oleh', 'role' => 'peninjau'],
                    ['role_label' => 'Disetujui Oleh', 'role' => 'penyetuju'],
                ],
                'stamp_on_published' => 'APPROVED',
            ],
        ];
    }

    /**
     * Schema "standar" (SOP/IK/SP) — layout identik, hanya label jenis berbeda.
     * Diturunkan dari docs/schema-sop.json, dikelompokkan ke DUA langkah form
     * (PRD v2 §4.1: satu sumber untuk form / preview / PDF).
     */
    private function standardSchema(string $code, string $label, string $flow = 'sop'): array
    {
        return [
            'doc_type' => $code,
            'doc_type_label' => $label,
            'header' => '_kop',
            'footer' => '_footer',
            'footer_text' => 'Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.',
            'approval_page' => '_pengesahan',
            'steps' => [
                [
                    'step' => 1,
                    'title' => 'Tujuan, Ruang Lingkup, Referensi & Definisi',
                    'sections' => [
                        [
                            'key' => 'tujuan', 'label' => 'I. TUJUAN', 'type' => 'rich_list',
                            'auto_number' => '1.', 'min_items' => 1, 'placeholder' => 'Masukkan poin tujuan...',
                        ],
                        [
                            'key' => 'ruang_lingkup', 'label' => 'II. RUANG LINGKUP', 'type' => 'rich_list',
                            'auto_number' => '2.', 'min_items' => 1, 'placeholder' => 'Masukkan poin ruang lingkup...',
                        ],
                        [
                            'key' => 'referensi', 'label' => 'III. REFERENSI', 'type' => 'reference_picker',
                            'auto_number' => '3.', 'allow_add' => true, 'placeholder' => 'Masukkan referensi...',
                            'suggestions' => [
                                'ISO 9001:2015 Sistem Manajemen Mutu',
                                'ISO 45001:2018 Sistem Manajemen K3',
                                'ISO 14001:2015 Sistem Manajemen Lingkungan',
                                'SMKP Minerba (Permen ESDM No. 26/2018)',
                                'UU No. 1 Tahun 1970 tentang Keselamatan Kerja',
                            ],
                        ],
                        [
                            'key' => 'definisi', 'label' => 'IV. DEFINISI', 'type' => 'rich_list',
                            'auto_number' => '4.', 'placeholder' => 'Masukkan definisi...',
                        ],
                    ],
                ],
                [
                    'step' => 2,
                    'title' => 'Aktivitas, Lampiran & Verifikasi',
                    'sections' => [
                        [
                            'key' => 'aktivitas', 'label' => 'V. AKTIVITAS DAN TANGGUNG JAWAB',
                            'type' => 'repeatable_group', 'auto_number' => '5.', 'min_groups' => 1,
                            'add_button_label' => '+ Tambah Aktivitas/Tanggung Jawab',
                            'group_fields' => [
                                ['key' => 'sub_judul', 'label' => 'Sub Judul', 'type' => 'text', 'placeholder' => 'Sub Judul (Contoh: Tahap Persiapan)'],
                                ['key' => 'deskripsi', 'label' => 'Deskripsi Aktivitas', 'type' => 'textarea', 'placeholder' => 'Deskripsi aktivitas...'],
                                ['key' => 'pic', 'label' => 'PIC', 'type' => 'text', 'placeholder' => 'PIC (Contoh: Tim ICT)'],
                            ],
                        ],
                        [
                            'key' => 'lampiran', 'label' => 'VI. LAMPIRAN',
                            'type' => 'repeatable_group', 'min_groups' => 0,
                            'add_button_label' => '+ Tambah Lampiran Baru',
                            'group_fields' => [
                                ['key' => 'judul', 'label' => 'Judul Lampiran', 'type' => 'text', 'placeholder' => 'Judul Lampiran (Contoh: Form Ceklis)'],
                                ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea', 'placeholder' => 'Keterangan / caption lampiran (opsional)...'],
                                ['key' => 'gambar', 'label' => 'Foto / Gambar (opsional)', 'type' => 'image', 'image_accept' => 'image/jpeg,image/png', 'image_max_mb' => 2],
                            ],
                        ],
                        ...$this->participantSections($flow),
                    ],
                ],
            ],
            'approval_page_layout' => $this->approvalLayout($flow),
        ];
    }

    /**
     * Schema IK (Instruksi Kerja) — HANYA tabel Aktivitas & Tanggung Jawab, lalu
     * langsung Halaman Pengesahan (tanpa Tujuan/Ruang Lingkup/Referensi/Definisi/
     * Lampiran). Alur satu SH/DH (dual). Referensi: docs/Instruksi-kerja.docx.
     */
    private function ikSchema(): array
    {
        return [
            'doc_type' => 'IK',
            'doc_type_label' => 'INSTRUKSI KERJA',
            'header' => '_kop',
            'footer' => '_footer',
            'footer_text' => 'Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.',
            'approval_page' => '_pengesahan',
            'steps' => [
                [
                    'step' => 1,
                    'title' => 'Aktivitas & Pengesahan',
                    'sections' => [
                        [
                            'key' => 'aktivitas', 'label' => 'I. AKTIVITAS DAN TANGGUNG JAWAB',
                            'type' => 'repeatable_group', 'auto_number' => '1.', 'min_groups' => 1,
                            'add_button_label' => '+ Tambah Aktivitas/Tanggung Jawab',
                            'group_fields' => [
                                ['key' => 'sub_judul', 'label' => 'Sub Judul', 'type' => 'text', 'placeholder' => 'Sub Judul (Contoh: Pemeliharaan bulanan)'],
                                ['key' => 'deskripsi', 'label' => 'Deskripsi Aktivitas', 'type' => 'textarea', 'placeholder' => 'Deskripsi aktivitas...'],
                                ['key' => 'pic', 'label' => 'PIC', 'type' => 'text', 'placeholder' => 'PIC (Contoh: ICT)'],
                            ],
                        ],
                        ...$this->participantSections('dual'),
                    ],
                ],
            ],
            'approval_page_layout' => $this->approvalLayout('dual'),
        ];
    }

    /**
     * Section user_picker langkah akhir per alur.
     * - 'sop'  → Peninjau (SH/DH) + Penyetuju (PJO) TERPISAH.
     * - 'dual' → SATU picker: SH/DH yang meninjau SEKALIGUS menyetujui (SP/IK).
     * Pembuat tambahan selalu ada (opsional).
     */
    private function participantSections(string $flow): array
    {
        $pembuatTambahan = [
            'key' => 'pembuat_tambahan', 'label' => 'Pembuat Tambahan (opsional)',
            'type' => 'user_picker', 'multiple' => true, 'required' => false,
            'hint' => 'Gunakan tombol + jika pembuat lebih dari 1 orang.',
        ];

        if ($flow === 'dual') {
            return [
                $pembuatTambahan,
                [
                    'key' => 'peninjau_penyetuju', 'label' => 'Ditinjau & Disetujui Oleh (SH/DH Dept)',
                    'type' => 'user_picker', 'role_filter' => ['section_head', 'departemen_head'], 'required' => true,
                ],
            ];
        }

        return [
            $pembuatTambahan,
            [
                'key' => 'peninjau', 'label' => 'Ditinjau Oleh (DH/SH)',
                'type' => 'user_picker', 'role_filter' => ['group_leader', 'section_head'], 'required' => true,
            ],
            [
                'key' => 'penyetuju', 'label' => 'Disetujui Oleh (PJO)',
                'type' => 'user_picker', 'role_filter' => ['pimpinan'], 'required' => true,
            ],
        ];
    }

    /** Tata letak Halaman Pengesahan per alur (3 baris SOP vs 2 baris SP/IK). */
    private function approvalLayout(string $flow): array
    {
        $rows = $flow === 'dual'
            ? [
                ['role_label' => 'Dibuat Oleh', 'role' => 'pembuat'],
                ['role_label' => 'Ditinjau dan Disetujui Oleh', 'role' => 'peninjau_penyetuju'],
            ]
            : [
                ['role_label' => 'Dibuat Oleh', 'role' => 'pembuat'],
                ['role_label' => 'Ditinjau Oleh', 'role' => 'peninjau'],
                ['role_label' => 'Disetujui Oleh', 'role' => 'penyetuju'],
            ];

        return [
            'columns' => ['Nama', 'Jabatan', 'Tanggal', 'Pengesahan'],
            'rows' => $rows,
            'stamp_on_published' => 'APPROVED',
        ];
    }
}
