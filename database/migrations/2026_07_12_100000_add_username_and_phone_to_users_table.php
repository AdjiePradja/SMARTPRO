<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Switch login credential to username (instead of email) and add a phone
 * number column. Additive & non-destructive:
 *   - adds `username` (unique) and `nomor_hp`
 *   - makes `email` nullable (kept for records/notifications, no longer used to log in)
 *   - backfills existing rows so the unique constraint holds
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('nomor_hp')->nullable()->after('jabatan');
            $table->string('email')->nullable()->change();
        });

        // Backfill existing accounts: username from email prefix, dummy phone.
        foreach (DB::table('users')->get() as $u) {
            DB::table('users')->where('id', $u->id)->update([
                'username' => $u->username ?? Str::slug(Str::before($u->email ?? 'user'.$u->id, '@'), ''),
                'nomor_hp' => $u->nomor_hp ?? '0812'.str_pad((string) $u->id, 8, '0', STR_PAD_LEFT),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'nomor_hp']);
        });
    }
};
