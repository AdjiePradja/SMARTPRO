<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Persetujuan final (PRD v2 §3). Approver = document.approver_id. Setuju ->
 * published/Berlaku; tolak (feedback wajib) -> kembali ke GL (rejected), dan
 * peninjau yang meloloskan diberi tahu (§3.4).
 */
class ApprovalController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /** Antrian "Persetujuan Saya". */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Document::with('type', 'department', 'creator', 'reviewer')
            ->where('status', 'pending_approval')
            ->latest('updated_at');

        if (! $user->can('document.view_all')) {
            $query->where('approver_id', $user->id);
        }

        return view('approvals.index', ['documents' => $query->paginate(15)]);
    }

    public function show(Request $request, Document $document)
    {
        $this->authorizeApprover($request, $document);

        return view('approvals.show', [
            'document' => $document->load('creator', 'reviewer', 'approver'),
        ]);
    }

    public function store(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeApprover($request, $document);

        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'comment' => 'required_if:decision,reject|nullable|string|max:2000',
        ]);

        if ($data['decision'] === 'approve') {
            $document->approvals()->create([
                'approver_id' => $request->user()->id,
                'decision' => 'approved',
                'signed_at' => now(),
            ]);
            $document->update(['status' => 'published', 'published_at' => now()]);
            $this->audit->log('document.approve', $document->id, ['status' => 'published']);
            $document->creator?->notify(new \App\Notifications\DocumentNotification(
                $document, "Dokumen {$document->doc_number} disetujui dan kini Berlaku.", 'bi-check-circle', 'documents.index'
            ));

            // Revisi Tipe B: versi lama yang "Sedang Direvisi" jadi obsolete (§3.3).
            if ($document->no_revisi > 0) {
                Document::where('doc_number', $document->doc_number)
                    ->where('id', '!=', $document->id)
                    ->where('status', 'sedang_direvisi')
                    ->update(['status' => 'obsolete']);
            }

            return redirect()->route('approvals.index')->with('status', "Dokumen {$document->doc_number} disetujui dan Berlaku.");
        }

        // Reject -> back to the flow as rejected; reviewer who passed it is notified.
        $document->approvals()->create([
            'approver_id' => $request->user()->id,
            'decision' => 'rejected',
            'comment' => $data['comment'],
            'signed_at' => now(),
        ]);
        $document->update(['status' => 'rejected']);
        $this->audit->log('document.approval_reject', $document->id, ['comment' => $data['comment']]);

        // Notify creator (perlu revisi) + the reviewer who passed it (§8).
        $document->creator?->notify(new \App\Notifications\DocumentNotification(
            $document, "Dokumen {$document->doc_number} ditolak approver — perlu revisi.", 'bi-x-circle', 'documents.revisi'
        ));
        $document->reviewer?->notify(new \App\Notifications\DocumentNotification(
            $document, "Dokumen {$document->doc_number} yang Anda loloskan ditolak approver.", 'bi-exclamation-triangle', 'review.index'
        ));

        return redirect()->route('approvals.index')->with('status', "Dokumen {$document->doc_number} ditolak dan dikembalikan.");
    }

    private function authorizeApprover(Request $request, Document $document): void
    {
        $user = $request->user();
        abort_unless($user->can('document.approve'), 403);
        abort_unless($document->approver_id === $user->id || $user->can('document.view_all'), 403, 'Anda bukan penyetuju dokumen ini.');
        abort_unless($document->status === 'pending_approval', 403, 'Dokumen tidak menunggu persetujuan.');
    }
}
