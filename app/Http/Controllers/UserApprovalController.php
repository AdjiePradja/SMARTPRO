<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Registration approval (roadmap Task 1.5). Admin IT sees all pending accounts;
 * Group Leader / Pimpinan see pending accounts in their own department.
 */
class UserApprovalController extends Controller
{
    // Authorization (can:user.approve_registration) is applied at the route level.
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $query = User::with('department', 'roles')
            ->where('status', 'pending')
            ->latest();

        // Admin IT sees everything; others are scoped to their department.
        if (! $user->can('user.manage')) {
            $query->where('department_id', $user->department_id);
        }

        return view('users.pending', [
            'pendingUsers' => $query->get(),
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $this->authorizeDepartment($request, $user);

        $user->update(['status' => 'active']);
        $this->audit->log('user.approve_registration', null, ['approved_user_id' => $user->id]);

        return back()->with('status', "Akun {$user->name} berhasil disetujui.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $this->authorizeDepartment($request, $user);

        $user->update(['status' => 'rejected']);
        $this->audit->log('user.reject_registration', null, ['rejected_user_id' => $user->id]);

        return back()->with('status', "Akun {$user->name} ditolak.");
    }

    /** Non-admins may only act on their own department's users. */
    private function authorizeDepartment(Request $request, User $user): void
    {
        $actor = $request->user();

        abort_if(
            ! $actor->can('user.manage') && $actor->department_id !== $user->department_id,
            403,
            'Anda hanya dapat mengelola user di departemen Anda.',
        );
    }
}
