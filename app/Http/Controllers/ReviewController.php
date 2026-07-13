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

    /** Antrian "Tinjau Dokumen". */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Document::with('type', 'department', 'creator')
            ->where('status', 'in_review')
            ->latest('submitted_at');

        // Assigned reviewer only (admin sees all).
        if (! $user->can('document.view_all')) {
            $query->where('reviewer_id', $user->id);
        }

        return view('review.index', ['documents' => $query->paginate(15)]);
    }

    /** Halaman tinjauan per-item. */
    public function show(Request $request, Document $document)
    {
        $this->authorizeReviewer($request, $document);

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
        ]);

        $review = $document->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'revision_round' => $document->revision_round,
            'decision' => $data['decision'] === 'approve' ? 'approved' : 'needs_revision',
            'summary' => $data['summary'] ?? null,
        ]);

        // Per-item feedback -> annotations.
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

        if ($data['decision'] === 'reject') {
            $document->update(['status' => 'rejected']);
            $this->audit->log('document.review_reject', $document->id, ['annotations' => $annotationCount]);
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

    private function authorizeReviewer(Request $request, Document $document): void
    {
        $user = $request->user();
        abort_unless($user->can('document.review'), 403);
        abort_unless($document->reviewer_id === $user->id || $user->can('document.view_all'), 403, 'Anda bukan peninjau dokumen ini.');
        abort_unless($document->status === 'in_review', 403, 'Dokumen tidak dalam status peninjauan.');
    }
}
