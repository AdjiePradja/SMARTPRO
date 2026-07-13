<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RBAC via spatie/laravel-permission (PRD v2 §2).
 *
 * Spatie roles mirror the four jabatan (staff, group_leader, section_head,
 * pimpinan) plus admin_it. The *functional* roles (Pembuat / Peninjau /
 * Approver) are contextual and resolved by Policies from jabatan + document
 * relationship — they are NOT stored as spatie roles.
 */
class RolePermissionSeeder extends Seeder
{
    public const ROLE_ADMIN = 'admin_it';
    public const ROLE_PIMPINAN = 'pimpinan';
    public const ROLE_SECTION_HEAD = 'section_head';
    public const ROLE_GROUP_LEADER = 'group_leader';
    public const ROLE_STAFF = 'staff';

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'document.create', 'document.edit', 'document.submit', 'document.delete',
            'document.review', 'document.approve', 'document.publish',
            'document.request_revision', 'document.change_status',
            'document.view_department', 'document.view_scope', 'document.view_all',
            'user.manage', 'user.approve_registration', 'user.create_staff',
            'audit.view',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $roles = [
            self::ROLE_ADMIN => $permissions, // full authority; every action audited (D11)
            self::ROLE_PIMPINAN => [
                'document.approve', 'document.publish', 'document.request_revision',
                'document.view_all', 'user.approve_registration', 'audit.view',
            ],
            self::ROLE_SECTION_HEAD => [
                'document.review', 'document.approve', 'document.request_revision',
                'document.view_department', 'user.approve_registration', 'audit.view',
            ],
            self::ROLE_GROUP_LEADER => [
                'document.create', 'document.edit', 'document.submit', 'document.delete',
                'document.review', 'document.request_revision',
                'document.view_department', 'user.approve_registration', 'audit.view',
            ],
            self::ROLE_STAFF => [
                'document.create', 'document.edit', 'document.submit', 'document.delete',
                'document.view_department',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            Role::firstOrCreate(['name' => $roleName])->syncPermissions($perms);
        }

        $this->migrateLegacyRole();
    }

    /** Move any v1 `user_dept` users to `staff`, then drop the obsolete role. */
    private function migrateLegacyRole(): void
    {
        $legacy = Role::where('name', 'user_dept')->first();
        if (! $legacy) {
            return;
        }

        User::role('user_dept')->get()->each(fn (User $u) => $u->syncRoles([self::ROLE_STAFF]));
        $legacy->delete();
    }
}
