<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\Ai\AiReviewerInterface;
use App\Services\AuditService;
use App\Services\SchemaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Peninjauan dokumen (PRD v2 §3). Peninjau = document.reviewer_id (ditentukan
 * saat pembuat mengisi, sesuai jabatan pembuat). Per-item annotations; lolos ->
 * pending_approval, ada temuan -> rejected (kembali ke pembuat).
 */
class ReviewController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /** Antrian "Tinjau Dokumen" + bagian "Status Revisi" (dokumen yang saya tolak). */
    public function index(Request $request)
    {
        $user = $request->user();
        $mine = fn ($q) => $user->can('document.view_all') ? $q : $q->where('reviewer_id', $user->id);

        // Perlu ditinjau: menunggu (belum disentuh) + sedang ditinjau.
        $documents = $mine(Document::with('type', 'department', 'creator')
            ->whereIn('status', ['waiting_for_review', 'in_review']))
            ->latest('submitted_at')->paginate(15);

        // Status Revisi: dokumen yang saya tolak — dipantau (bisa Batalkan Revisi) (v3.1 §4.3).
        $statusRevisi = $mine(Document::with('type', 'department', 'creator')
            ->where('status', 'rejected'))
            ->latest('updated_at')->get();

        return view('review.index', compact('documents', 'statusRevisi'));
    }

    /** Halaman tinjauan per-item. Membuka dokumen menandai in_review (v3.1 §4.2). */
    public function show(Request $request, Document $document)
    {
        $this->authorizeReviewer($request, $document, ['waiting_for_review', 'in_review']);

        // Reviewer mulai menyentuh → in_review (pembuat tak bisa Tarik lagi).
        if ($document->status === 'waiting_for_review') {
            $document->update(['status' => 'in_review']);
            $this->audit->log('document.review_start', $document->id);
        }

        $schema = SchemaService::for($document->type);

        // Annotations from previous rounds stay visible during revision (§3.3).
        $priorAnnotations = $document->reviews()
            ->with('annotations')
            ->get()
            ->flatMap->annotations
            ->groupBy('section_key');

        return view('review.show', [
            'document' => $document,
            'schema' => $schema,
            'contentMap' => $document->contentMap(),
            'priorAnnotations' => $priorAnnotations,
            'imageAttachments' => $document->attachments()->where('mime', 'like', 'image/%')->with('comments.user')->get(),
        ]);
    }

    /** Simpan keputusan tinjauan. */
    public function store(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeReviewer($request, $document);

        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'summary' => 'nullable|string|max:2000',
            'annotations' => 'array',
            'annotations.*.*' => 'nullable|string|max:2000',
            'annotations_ai' => 'array',
            // Checklist JSA (#2): tanda per pengendalian {refP: check|cross|''}.
            // String kosong ('') dikirim utk yg tak ditandai → izinkan; array_filter
            // di bawah hanya menyimpan check/cross.
            'checklist' => 'array',
            'checklist.*' => 'nullable|string|max:10',
        ]);
        $aiFlags = $request->input('annotations_ai', []);

        // Checklist "Beri tanda" JSA → disimpan sbg konten dokumen (dibaca saat cetak
        // PDF). Hanya tanda TERPILIH (check/cross) yang disimpan; sisanya kosong.
        $marks = array_filter(
            $request->input('checklist', []),
            fn ($v) => in_array($v, ['check', 'cross'], true)
        );
        app(\App\Services\DocumentService::class)->saveSection($document, 'jsa_checklist', $marks);

        $review = $document->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'revision_round' => $document->revision_round,
            'decision' => $data['decision'] === 'approve' ? 'approved' : 'needs_revision',
            'summary' => $data['summary'] ?? null,
        ]);

        // Per-item feedback -> annotations. Tandai asal AI bila diadopsi (§11.3).
        $annotationCount = 0;
        $aiAdoptedCount = 0;
        foreach (($data['annotations'] ?? []) as $sectionKey => $items) {
            foreach ($items as $itemRef => $comment) {
                if (blank($comment)) {
                    continue;
                }
                $fromAi = ! empty($aiFlags[$sectionKey][$itemRef]) && $aiFlags[$sectionKey][$itemRef] == '1';
                $review->annotations()->create([
                    'section_key' => $sectionKey,
                    'item_ref' => (string) $itemRef,
                    'severity' => 'minor',
                    'comment' => $comment,
                    'ai_generated' => $fromAi,
                    'ai_adopted' => $fromAi,
                ]);
                $annotationCount++;
                $fromAi && $aiAdoptedCount++;
            }
        }

        if ($data['decision'] === 'reject') {
            $document->update(['status' => 'rejected']);
            $this->audit->log('document.review_reject', $document->id, ['annotations' => $annotationCount, 'ai_adopted' => $aiAdoptedCount]);
            $document->creator?->notify(new \App\Notifications\DocumentNotification(
                $document, "Dokumen {$document->doc_number} dikembalikan untuk revisi.", 'bi-arrow-counterclockwise', 'documents.revisi'
            ));
            $message = "Dokumen {$document->doc_number} dikembalikan untuk revisi.";
        } else {
            $document->update(['status' => 'pending_approval']);
            $this->audit->log('document.review_approve', $document->id);
            $document->approver?->notify(new \App\Notifications\DocumentNotification(
                $document, "Dokumen {$document->doc_number} perlu disetujui.", 'bi-patch-check', 'approvals.index'
            ));
            $message = "Dokumen {$document->doc_number} diloloskan ke persetujuan.";
        }

        return redirect()->route('review.index')->with('status', $message);
    }

    /**
     * AI Review Assist (PRD v2 §9). Mengirim isi dokumen ke provider AI dan
     * mengembalikan ringkasan + temuan terstruktur. AI tidak pernah
     * approve/reject — hanya membantu peninjau. Aktif hanya bila AI_ENABLED.
     */
    public function aiAnalyze(Request $request, Document $document, AiReviewerInterface $ai): JsonResponse
    {
        $this->authorizeReviewer($request, $document);

        if (! config('services.ai.enabled') || ! $ai->isEnabled()) {
            return response()->json([
                'enabled' => false,
                'summary' => 'Fitur AI Review sedang dinonaktifkan.',
                'findings' => [],
            ]);
        }

        $result = $ai->review($document, $document->contentMap());
        $this->audit->log('document.ai_review', $document->id, ['findings' => count($result['findings'] ?? [])]);

        return response()->json(array_merge(['enabled' => true], $result));
    }

    /**
     * Batalkan Revisi (v3.1 §4.3): peninjau menarik penolakannya. Dokumen
     * `rejected` → kembali `in_review` (GL periksa ulang; antisipasi reviewer
     * salah tulis). Untuk dokumen tipe B lihat Approval/DocumentController.
     */
    public function cancelRevision(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeReviewer($request, $document, ['rejected']);

        $document->update(['status' => 'in_review']);
        $this->audit->log('document.cancel_revision', $document->id, ['from' => 'rejected']);
        $document->creator?->notify(new \App\Notifications\DocumentNotification(
            $document, "Penolakan dokumen {$document->doc_number} dibatalkan peninjau — sedang ditinjau ulang.", 'bi-arrow-repeat', 'documents.index'
        ));

        return back()->with('status', "Revisi {$document->doc_number} dibatalkan; dokumen ditinjau ulang.");
    }

    private function authorizeReviewer(Request $request, Document $document, array $allowedStatuses = ['in_review']): void
    {
        $user = $request->user();
        abort_unless($user->can('document.review'), 403);
        abort_unless($document->reviewer_id === $user->id || $user->can('document.view_all'), 403, 'Anda bukan peninjau dokumen ini.');
        abort_unless(in_array($document->status, $allowedStatuses, true), 403, 'Status dokumen tidak sesuai untuk aksi ini.');
    }
}
