<?php

namespace App\Http\Requests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nrp' => ['required', 'string', 'max:50', 'unique:users,nrp'],
            'nomor_hp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            // Pimpinan tanpa departemen (v3.1 §3.1); Admin dept opsional.
            'department_id' => ['nullable', 'exists:departments,id', 'required_unless:role,'.RolePermissionSeeder::ROLE_PIMPINAN.','.RolePermissionSeeder::ROLE_ADMIN],
            'role' => ['required', Rule::in(self::assignableRoles())],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /** Roles (mirroring jabatan) an Admin IT may assign when creating a staff account. */
    public static function assignableRoles(): array
    {
        return [
            RolePermissionSeeder::ROLE_ADMIN,
            RolePermissionSeeder::ROLE_PIMPINAN,
            RolePermissionSeeder::ROLE_SECTION_HEAD,
            RolePermissionSeeder::ROLE_DEPARTEMEN_HEAD,
            RolePermissionSeeder::ROLE_GROUP_LEADER,
            RolePermissionSeeder::ROLE_STAFF,
        ];
    }

    public function messages(): array
    {
        return [
            'nrp.unique' => 'NRP ini sudah terdaftar.',
            'email.unique' => 'Email ini sudah terdaftar.',
        ];
    }
}
