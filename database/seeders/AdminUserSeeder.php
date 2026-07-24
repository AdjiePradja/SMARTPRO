<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds satu akun contoh per PERAN (baseline "fresh install"): Admin IT, PJO,
 * Section Head, Departemen Head, Group Leader, dan Non-Staff. Password: "password".
 *
 * Login is by NRP. Spatie role is kept in sync with jabatan.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $dept = fn (?string $code) => $code ? Department::where('code', $code)->value('id') : null;

        // [name, nrp, jabatan, department, role] — satu per peran.
        $accounts = [
            ['Admin IT',            'ADM-0001', null,                            'ICTMD', RolePermissionSeeder::ROLE_ADMIN],
            ['Wahyu Binuko',        'PJO-0001', User::JABATAN_PIMPINAN,          null,    RolePermissionSeeder::ROLE_PIMPINAN], // Pimpinan tanpa dept (v3.1 §3.1)
            ['Arisal Farzan',       'SH-0001',  User::JABATAN_SECTION_HEAD,      'ICTMD', RolePermissionSeeder::ROLE_SECTION_HEAD],
            ['Dwi Hendra',          'DH-0001',  User::JABATAN_DEPARTEMEN_HEAD,   'ICTMD', RolePermissionSeeder::ROLE_DEPARTEMEN_HEAD],
            ['Angga Margi Saputro', 'GL-0001',  User::JABATAN_GROUP_LEADER,      'ICTMD', RolePermissionSeeder::ROLE_GROUP_LEADER],
            ['Staff ICTMD',         'STF-0001', User::JABATAN_STAFF,             'ICTMD', RolePermissionSeeder::ROLE_STAFF],
        ];

        foreach ($accounts as [$name, $nrp, $jabatan, $deptCode, $role]) {
            $user = User::updateOrCreate(
                ['nrp' => $nrp],
                [
                    'name' => $name,
                    'jabatan' => $jabatan,
                    'department_id' => $dept($deptCode),
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$role]);
        }
    }
}
