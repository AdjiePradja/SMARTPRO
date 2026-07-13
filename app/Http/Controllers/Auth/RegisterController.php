<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function create()
    {
        return view('auth.register', [
            'departments' => Department::orderBy('code')->get(),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        // Self-registration always creates a pending Staff account (PRD v2 §7.1).
        $user = User::create([
            'name' => $request->name,
            'nrp' => $request->nrp,
            'jabatan' => User::JABATAN_STAFF,
            'nomor_hp' => $request->nomor_hp,
            'department_id' => $request->department_id,
            'password' => Hash::make($request->password),
            'status' => 'pending',
        ]);

        $user->assignRole(RolePermissionSeeder::ROLE_STAFF);

        $this->audit->log('user.register', null, ['user_id' => $user->id, 'department_id' => $user->department_id]);

        // Log them in so they land on the "waiting for approval" page.
        Auth::login($user);

        return redirect()->route('pending');
    }
}
