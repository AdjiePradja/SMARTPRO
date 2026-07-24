<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Komentar pada foto lampiran (PRD v3.1 §6.2). Bisa diberikan pembuat (saat
 * pembuatan) & peninjau (saat tinjau) — siapa pun yang berhak melihat dokumen.
 */
class AttachmentCommentController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function store(Request $request, Attachment $attachment): RedirectResponse
    {
        $document = $attachment->document;
        $user = $request->user();

        // Boleh berkomentar bila: pembuat, peninjau, penyetuju, atau bisa lihat semua.
        $allowed = in_array($user->id, [$document->created_by, $document->reviewer_id, $document->approver_id], true)
            || $user->can('document.view_all');
        abort_unless($allowed, 403);

        $data = $request->validate(['comment' => 'required|string|max:1000']);

        $attachment->comments()->create(['user_id' => $user->id, 'comment' => $data['comment']]);
        $this->audit->log('attachment.comment', $document->id, ['attachment_id' => $attachment->id]);

        return back()->with('status', 'Komentar lampiran ditambahkan.');
    }
}
