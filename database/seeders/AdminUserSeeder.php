<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the initial Admin IT account plus one test account per jabatan and
 * THREE dummy Group Leaders in different departments (INSTRUKSI Fase 1 §5) —
 * these populate the peninjau/approver dropdowns. Password: "password".
 *
 * Login is by NRP. Spatie role is kept in sync with jabatan.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $dept = fn (string $code) => Department::where('code', $code)->value('id');

        // [name, nrp, jabatan, department, role]
        $accounts = [
            ['Admin IT',            'ADM-0001', null,                          'ICTMD',    RolePermissionSeeder::ROLE_ADMIN],
            ['Wahyu Binuko',        'PJO-0001', User::JABATAN_PIMPINAN,        'ICTMD',    RolePermissionSeeder::ROLE_PIMPINAN],
            ['Arisal Farzan',       'SH-0001',  User::JABATAN_SECTION_HEAD,    'ICTMD',    RolePermissionSeeder::ROLE_SECTION_HEAD],
            ['Angga Margi Saputro', 'GL-0001',  User::JABATAN_GROUP_LEADER,    'ICTMD',    RolePermissionSeeder::ROLE_GROUP_LEADER],
            ['Budi Santoso',        'GL-0002',  User::JABATAN_GROUP_LEADER,    'PLANT',    RolePermissionSeeder::ROLE_GROUP_LEADER],
            ['Citra Dewi',          'GL-0003',  User::JABATAN_GROUP_LEADER,    'SHE',      RolePermissionSeeder::ROLE_GROUP_LEADER],
            ['Staff ICTMD',         'STF-0001', User::JABATAN_STAFF,           'ICTMD',    RolePermissionSeeder::ROLE_STAFF],
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
