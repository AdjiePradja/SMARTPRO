<?php

namespace App\Console\Commands;

use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * "Renewal" — mengosongkan SELURUH data operasional agar aplikasi seolah dipakai
 * dari awal, TANPA menghapus konfigurasi (roles, permission, departemen, jenis
 * dokumen). Bukan migrate:fresh (CLAUDE.md §4 melarang penghapus-skema): perintah
 * ini bertarget, terulang, dan auditable.
 *
 * Yang DIKOSONGKAN: dokumen + seluruh turunannya, lampiran (baris & berkas),
 * tinjauan/persetujuan, notifikasi, audit log, dan seluruh user.
 * Yang DIPULIHKAN: konfigurasi (via seeder) + satu akun contoh per peran.
 */
class RenewData extends Command
{
    protected $signature = 'smartpro:renew {--force : Lewati konfirmasi}';

    protected $description = 'Kosongkan data operasional (dokumen, log, user) & pulihkan baseline konfigurasi + akun contoh.';

    /** Tabel operasional yang dikosongkan (urutan tak masalah — FK dimatikan). */
    private const OPERATIONAL_TABLES = [
        'review_annotations', 'reviews', 'approvals',
        'attachment_comments', 'attachments',
        'document_authors', 'document_contents', 'document_versions', 'documents',
        'audit_logs', 'notifications',
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'Kosongkan SEMUA dokumen, lampiran, notifikasi, audit log, dan user? '
            .'Konfigurasi (roles/departemen/jenis) tetap. Lanjutkan?'
        )) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        Schema::disableForeignKeyConstraints();
        foreach (self::OPERATIONAL_TABLES as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  dikosongkan: {$table}");
            }
        }
        DB::table('users')->truncate();
        $this->line('  dikosongkan: users');

        // Tautan spatie (model_has_roles) ikut kosong agar tak menggantung.
        foreach (['model_has_roles', 'model_has_permissions'] as $pivot) {
            if (Schema::hasTable($pivot)) {
                DB::table($pivot)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();

        // Berkas lampiran fisik.
        $lampiran = storage_path('app/public/lampiran');
        if (File::isDirectory($lampiran)) {
            File::cleanDirectory($lampiran);
            $this->line('  dibersihkan: storage/app/public/lampiran');
        }

        // Pulihkan konfigurasi + akun contoh (idempotent).
        $this->line('');
        $this->info('Memulihkan baseline konfigurasi & akun contoh...');
        foreach ([DepartmentSeeder::class, RolePermissionSeeder::class, DocumentTypeSeeder::class, AdminUserSeeder::class] as $seeder) {
            $this->callSilent('db:seed', ['--class' => $seeder, '--force' => true]);
        }

        $this->newLine();
        $this->info('Selesai. Data operasional kosong; konfigurasi & 6 akun contoh (satu per peran) siap.');
        $this->line('Akun: ADM-0001, PJO-0001, SH-0001, DH-0001, GL-0001, STF-0001 — password "password".');

        return self::SUCCESS;
    }
}
