<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Documents visible to this user (strict per-dept unless view_all, D12).
        $visible = fn () => Document::query()->when(
            ! $user->can('document.view_all'),
            fn ($q) => $q->where(fn ($w) => $w->where('department_id', $user->department_id)->orWhere('created_by', $user->id)),
        );

        // Status distribution (for the chart) — only meaningful statuses.
        $statusOrder = ['draft', 'in_review', 'rejected', 'pending_approval', 'published', 'sedang_direvisi', 'obsolete'];
        $statusCounts = $visible()->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $chart = [];
        foreach ($statusOrder as $s) {
            $chart[$s] = (int) ($statusCounts[$s] ?? 0);
        }

        $stats = [
            'total' => $visible()->count(),
            'my_documents' => Document::where('created_by', $user->id)->count(),
            'published' => $visible()->where('status', 'published')->count(),
            'need_revision' => Document::where('created_by', $user->id)->where('status', 'rejected')->count(),
        ];

        // Role-specific action counters.
        $queues = [];
        if ($user->can('document.review')) {
            $queues['review'] = Document::where('status', 'in_review')
                ->when(! $user->can('document.view_all'), fn ($q) => $q->where('reviewer_id', $user->id))->count();
        }
        if ($user->can('document.approve')) {
            $queues['approval'] = Document::where('status', 'pending_approval')
                ->when(! $user->can('document.view_all'), fn ($q) => $q->where('approver_id', $user->id))->count();
        }
        if ($user->can('user.approve_registration')) {
            $queues['pending_users'] = User::where('status', 'pending')
                ->when(! $user->can('user.manage'), fn ($q) => $q->where('department_id', $user->department_id))->count();
        }

        return view('dashboard', [
            'user' => $user,
            'stats' => $stats,
            'chart' => $chart,
            'queues' => $queues,
            'departments' => $user->can('document.view_all') ? Department::count() : null,
            'recent' => $visible()->with('type', 'department')->latest()->limit(6)->get(),
        ]);
    }
}
