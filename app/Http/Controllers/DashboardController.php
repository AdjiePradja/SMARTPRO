<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
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

        // Peran dashboard: GL = pembuat (kartu "Perlu Revisi" + "Dokumen Ditolak");
        // SH/DH/PJO = peninjau/penyetuju (kartu "Sedang Revisi", tak merevisi sendiri).
        $isCreator = $user->jabatan === 'group_leader';
        $isPjo = $user->jabatan === 'pimpinan';
        // SH/DH memimpin departemen → kartu ke-2 menampilkan dokumen DEPARTEMEN
        // (bukan "Dokumen Saya"), sesuai lingkup wewenang mereka (#4).
        $isDeptHead = in_array($user->jabatan, ['section_head', 'departemen_head'], true);

        // "Perlu Revisi" (GL) = dokumen DITOLAK + draft AJUKAN REVISI (revises_document_id),
        // identik dgn menu "Dokumen Revisi" (DocumentController::revisi).
        $needRevision = Document::where('created_by', $user->id)
            ->where(fn ($q) => $q->where('status', 'rejected')
                ->orWhere(fn ($w) => $w->whereNotNull('revises_document_id')->where('status', 'draft')))
            ->count();

        $stats = [
            'total' => $visible()->count(),
            // Kartu GL "Dokumen Saya" — samakan dgn daftar (index): buatannya, kecuali
            // versi sedang_direvisi/obsolete (yg disembunyikan di daftar).
            'my_documents' => Document::where('created_by', $user->id)
                ->whereNotIn('status', ['sedang_direvisi', 'obsolete'])->count(),
            // Dokumen se-departemen (SH/DH) — dipakai kartu ke-2 menggantikan "Dokumen Saya" (#4).
            'dept_documents' => $user->department_id
                ? Document::where('department_id', $user->department_id)->count()
                : 0,
            'published' => $visible()->where('status', 'published')->count(),
            'need_revision' => $needRevision,                                                      // GL: kartu "Perlu Revisi"
            'rejected' => Document::where('created_by', $user->id)->where('status', 'rejected')->count(), // GL: kartu "Dokumen Ditolak"
            'sedang_direvisi' => $visible()->where('status', 'sedang_direvisi')->count(),          // SH/DH/PJO: kartu "Sedang Revisi"
        ];

        // Role-specific action counters.
        $queues = [];
        if ($user->can('document.review')) {
            // Sama dgn menu Tinjau Dokumen (ReviewController::index): dokumen SUDAH
            // dikirim GL berstatus waiting_for_review dan baru jadi in_review saat
            // peninjau membukanya — keduanya harus dihitung "Perlu Ditinjau" (#1).
            $queues['review'] = Document::whereIn('status', ['waiting_for_review', 'in_review'])
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

        // Dokumen Overview: jumlah dokumen dibuat per bulan (8 bulan terakhir).
        $monthly = [];
        $base = now()->startOfMonth();
        for ($i = 7; $i >= 0; $i--) {
            $m = (clone $base)->subMonths($i);
            $monthly[] = [
                'label' => $m->translatedFormat('M'),
                'count' => (int) $visible()->whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count(),
            ];
        }

        // Distribusi dokumen: matriks status × jenis (SOP/IK/SP/JSA) + total.
        $jenisList = ['SOP', 'IK', 'SP', 'JSA'];
        $matrixStatuses = [
            'published' => 'Berlaku', 'draft' => 'Draft', 'in_review' => 'Dalam Peninjauan',
            'pending_approval' => 'Menunggu Persetujuan', 'rejected' => 'Ditolak', 'sedang_direvisi' => 'Sedang Direvisi',
        ];
        $rawMatrix = $visible()
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->selectRaw('documents.status AS s, document_types.code AS j, COUNT(*) AS c')
            ->groupBy('documents.status', 'document_types.code')
            ->get();
        $matrix = [];
        foreach ($matrixStatuses as $key => $label) {
            $row = ['label' => $label, 'status' => $key, 'per' => array_fill_keys($jenisList, 0), 'total' => 0];
            foreach ($rawMatrix->where('s', $key) as $r) {
                if (isset($row['per'][$r->j])) {
                    $row['per'][$r->j] = (int) $r->c;
                    $row['total'] += (int) $r->c;
                }
            }
            $matrix[] = $row;
        }

        // Sapaan personal berdasarkan jam WITA (§8).
        $hour = now()->hour;
        $greeting = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        // Feed aktivitas terbaru dari audit_logs (bukan documents), difilter hak akses.
        $activityQuery = AuditLog::with('user', 'document')->latest('created_at');
        if (! $user->can('document.view_all')) {
            $activityQuery->where(fn ($q) => $q
                ->where('user_id', $user->id)
                ->orWhereHas('document', fn ($d) => $d->where('department_id', $user->department_id)));
        }

        return view('dashboard', [
            'user' => $user,
            'greeting' => $greeting,
            'stats' => $stats,
            'chart' => $chart,
            'queues' => $queues,
            'departments' => $user->can('document.view_all') ? Department::count() : null,
            'isCreator' => $isCreator,
            'isPjo' => $isPjo,
            'isDeptHead' => $isDeptHead,
            // Log aktivitas: 7 poin utk GL & PJO (menutup celah tinggi ke Distribusi),
            // 6 utk SH/DH (ada kartu Akun Menunggu di atasnya).
            'activities' => $activityQuery->limit($isCreator || $isPjo ? 7 : 6)->get(),
            'monthly' => $monthly,
            'matrix' => $matrix,
            'jenisList' => $jenisList,
        ]);
    }
}
