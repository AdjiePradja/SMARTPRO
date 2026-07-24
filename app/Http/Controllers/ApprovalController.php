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

        // Halaman PJO SENGAJA ringkas: keputusan + alasan saja. Penilaian per-item
        // (termasuk analisa JSA) adalah tugas PENINJAU, tak diduplikasi di sini.
        return view('approvals.show', [
            'document' => $document->load('creator', 'reviewer', 'approver'),
        ]);
    }

    public function store(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeApprover($request, $document);

        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'comment' => 'nullable|string|max:2000',
            'summary' => 'nullable|string|max:2000',
            'annotations' => 'array',
            'annotations.*.*' => 'nullable|string|max:2000',
        ]);

        // Menolak/ajukan revisi wajib beri alasan: ringkasan ATAU minimal satu catatan.
        if ($data['decision'] === 'reject') {
            $hasAnnotation = collect($data['annotations'] ?? [])->flatten()->filter(fn ($v) => filled($v))->isNotEmpty();
            if (blank($data['summary'] ?? null) && blank($data['comment'] ?? null) && ! $hasAnnotation) {
                return back()->withInput()->withErrors(['summary' => 'Beri ringkasan atau minimal satu catatan revisi sebelum mengembalikan dokumen.']);
            }
        }

        if ($data['decision'] === 'approve') {
            $document->approvals()->create([
                'approver_id' => $request->user()->id,
                'decision' => 'approved',
                'signed_at' => now(),
            ]);

            // Deteksi revisi Tipe B: ada versi lama "Sedang Direvisi" bernomor sama.
            // JANGAN pakai no_revisi > 0 — roll-over edisi mengembalikan revisi ke 0
            // (Edisi 2 Rev 0 adalah revisi, bukan dokumen baru).
            $oldVersion = Document::where('doc_number', $document->doc_number)
                ->where('id', '!=', $document->id)
                ->where('status', 'sedang_direvisi');
            $isRevision = $oldVersion->exists();

            // Kunci nomor FINAL saat approved (v3.1 §5) — hanya dokumen terbit
            // memakan nomor final → tak bolong. (Revisi tipe B mempertahankan nomor lama.)
            $final = $document->doc_number_final;
            if (! $final) {
                $final = $isRevision
                    ? ($document->doc_number_temp ?? $document->doc_number) // revisi warisi nomor
                    : app(\App\Services\DocumentNumberService::class)->generateFinal($document->type, $document->department);
            }

            $document->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $final]);
            $this->audit->log('document.approve', $document->id, ['status' => 'published', 'doc_number_final' => $final]);
            $document->creator?->notify(new \App\Notifications\DocumentNotification(
                $document, "Dokumen {$document->doc_number} disetujui dan kini Berlaku.", 'bi-check-circle', 'documents.index'
            ));

            // Revisi Tipe B: versi lama yang "Sedang Direvisi" otomatis jadi
            // TIDAK BERLAKU (obsolete) begitu versi baru disahkan (§3.3).
            if ($isRevision) {
                $oldVersion->update(['status' => 'obsolete']);
            }

            return redirect()->route('approvals.index')->with('status', "Dokumen {$document->doc_number} disetujui dan Berlaku.");
        }

        // Reject -> back to the flow as rejected; reviewer who passed it is notified.
        $summary = $data['summary'] ?? $data['comment'] ?? null;
        $document->approvals()->create([
            'approver_id' => $request->user()->id,
            'decision' => 'rejected',
            'comment' => $summary,
            'signed_at' => now(),
        ]);

        // Simpan catatan per-item sbg Review (decision needs_revision) supaya
        // TAMPIL di form revisi pembuat persis spt catatan peninjau (#4). Diberi
        // penanda "[Penyetuju]" agar pembuat tahu asalnya dari approver.
        $review = $document->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'revision_round' => $document->revision_round,
            'decision' => 'needs_revision',
            'summary' => $summary ? '[Penyetuju] '.$summary : '[Penyetuju] Mengajukan revisi.',
        ]);
        $annotationCount = 0;
        foreach (($data['annotations'] ?? []) as $sectionKey => $items) {
            foreach ($items as $itemRef => $comment) {
                if (blank($comment)) {
                    continue;
                }
                $review->annotations()->create([
                    'section_key' => $sectionKey,
                    'item_ref' => (string) $itemRef,
                    'severity' => 'minor',
                    'comment' => $comment,
                ]);
                $annotationCount++;
            }
        }

        $document->update(['status' => 'rejected']);
        $this->audit->log('document.approval_reject', $document->id, ['comment' => $summary, 'annotations' => $annotationCount]);

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
