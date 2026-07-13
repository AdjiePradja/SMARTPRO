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
        DocumentType::updateOrCreate(
            ['code' => 'SOP'],
            [
                'name' => 'Standard Operating Procedure',
                'class' => 'inti',
                'scope' => 'all_departments',
                'is_active' => true,
                'schema_json' => $this->sopSchema(),
            ],
        );

        // IK, SP, JSA are core document types but their schemas await sample
        // documents (CLAUDE.md §10 — do not fabricate). Seeded as inactive
        // placeholders so the "Dokumen Baru" dropdown can list all four.
        $placeholders = [
            'IK' => 'Instruksi Kerja',
            'SP' => 'Standar Parameter',
            'JSA' => 'Job Safety Analysis',
        ];

        foreach ($placeholders as $code => $name) {
            DocumentType::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'class' => 'inti',
                    'scope' => 'all_departments',
                    'is_active' => false, // schema not ready yet
                    'schema_json' => [
                        'doc_type' => $code,
                        'header' => 'ppa_standard_header',
                        'footer' => 'ppa_standard_footer',
                        'approval_page' => 'ppa_pengesahan',
                        'steps' => [],
                    ],
                ],
            );
        }
    }

    private function sopSchema(): array
    {
        // Operational SOP schema derived from docs/schema-sop.json, grouped into
        // the TWO form steps required by PRD v2 §4.1 (single source for
        // form / preview / PDF).
        return [
            'doc_type' => 'SOP',
            'doc_type_label' => 'STANDARD OPERATING PROCEDURE',
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
                                ['key' => 'isi', 'label' => 'Isi Lampiran (pilih salah satu: teks atau gambar)', 'type' => 'text_or_image',
                                    'text_placeholder' => 'Masukkan keterangan teks jika ada...', 'image_accept' => 'image/jpeg,image/png', 'image_max_mb' => 2],
                            ],
                        ],
                        [
                            'key' => 'pembuat_tambahan', 'label' => 'Pembuat Tambahan (opsional)',
                            'type' => 'user_picker', 'multiple' => true, 'required' => false,
                            'hint' => 'Gunakan tombol + jika pembuat lebih dari 1 orang.',
                        ],
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
}
