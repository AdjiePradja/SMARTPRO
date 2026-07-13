<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Admin IT user management (roadmap Task 1.6). Admin lists all users and
 * creates staff accounts (GL / Section Head / Pimpinan / User / Admin) with a
 * role and department. All actions are audited (D11).
 *
 * Route-level authorization: can:user.manage.
 */
class UserManagementController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request)
    {
        $query = User::with('department', 'roles');

        if ($search = $request->input('q')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('nrp', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($dept = $request->input('department_id')) {
            $query->where('department_id', $dept);
        }

        return view('users.index', [
            'users' => $query->latest()->paginate(15)->withQueryString(),
            'departments' => Department::orderBy('code')->get(),
            'filters' => $request->only('q', 'department_id'),
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'departments' => Department::orderBy('code')->get(),
            'roles' => StoreUserRequest::assignableRoles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        // jabatan mirrors the chosen role, except Admin IT (not a document-flow jabatan).
        $jabatan = $request->role === \Database\Seeders\RolePermissionSeeder::ROLE_ADMIN
            ? null
            : $request->role;

        $user = User::create([
            'name' => $request->name,
            'nrp' => $request->nrp,
            'jabatan' => $jabatan,
            'nomor_hp' => $request->nomor_hp,
            'email' => $request->email,
            'department_id' => $request->department_id,
            'password' => Hash::make($request->password),
            'status' => 'active', // admin-created staff accounts are active immediately
        ]);

        $user->assignRole($request->role);

        $this->audit->log('user.create_staff', null, [
            'created_user_id' => $user->id,
            'role' => $request->role,
            'department_id' => $user->department_id,
        ]);

        return redirect()->route('users.index')
            ->with('status', "Akun {$user->name} ({$request->role}) berhasil dibuat.");
    }

    /** Toggle active/rejected status (soft deactivation). */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Tidak dapat mengubah status akun sendiri.');

        $new = $user->status === 'active' ? 'rejected' : 'active';
        $user->update(['status' => $new]);

        $this->audit->log('user.toggle_status', null, ['user_id' => $user->id, 'status' => $new]);

        return back()->with('status', "Status {$user->name} diubah menjadi {$new}.");
    }
}
