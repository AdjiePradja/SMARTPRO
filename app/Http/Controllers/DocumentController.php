<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentNumberService;
use App\Services\DocumentService;
use App\Services\SchemaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly DocumentNumberService $numbering,
    ) {}

    /** "Status Dokumen Saya" — documents the user can see (strict per-dept, D12). */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Document::with('type', 'department', 'creator')->latest();

        // Admin IT sees all 7 departments; everyone else is scoped to their own.
        if (! $user->can('document.view_all')) {
            $query->where(fn ($q) => $q
                ->where('department_id', $user->department_id)
                ->orWhere('created_by', $user->id));
        }

        // Search + filter (Fase 7).
        if ($q = $request->input('q')) {
            $query->where(fn ($w) => $w->where('doc_number', 'like', "%{$q}%")->orWhere('title', 'like', "%{$q}%"));
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->input('type')) {
            $query->whereHas('type', fn ($t) => $t->where('code', $type));
        }

        return view('documents.index', [
            'documents' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only('q', 'status', 'type'),
            'types' => \App\Models\DocumentType::orderBy('code')->pluck('code'),
        ]);
    }

    /**
     * "Dokumen Baru — Langkah 1" (Task 2.3). The document type is chosen from
     * the sidebar dropdown and passed as ?type=CODE; the form itself no longer
     * has a type selector. Inactive types (schema not ready) show a notice.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $typeCode = strtoupper($request->query('type', 'SOP'));
        $type = DocumentType::where('code', $typeCode)->first();

        abort_if(! $type, 404, "Jenis dokumen {$typeCode} tidak dikenal.");

        // Schema not ready yet (IK/SP/JSA await samples).
        if (! $type->is_active || empty($type->schema_json['steps'] ?? [])) {
            return view('documents.unavailable', ['type' => $type]);
        }

        // Non-admins are locked to their own department; admin may pick any.
        $canChooseDept = $user->can('document.view_all');
        $departments = $canChooseDept ? Department::orderBy('code')->get() : Department::where('id', $user->department_id)->get();
        $defaultDept = $departments->firstWhere('id', $user->department_id) ?? $departments->first();

        $numberPreview = $defaultDept ? $this->numbering->generate($type, $defaultDept) : '—';

        return view('documents.create', compact('type', 'departments', 'defaultDept', 'canChooseDept', 'numberPreview'));
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $type = DocumentType::findOrFail($request->document_type_id);

        abort_unless($type->is_active, 422, 'Jenis dokumen ini belum tersedia.');

        // Enforce department scope: non-admins can only create for their own dept.
        $departmentId = $user->can('document.view_all') ? $request->department_id : $user->department_id;
        $department = Department::findOrFail($departmentId);

        $document = $this->documents->createDraft(
            creator: $user,
            type: $type,
            department: $department,
            title: $request->title,
            manualNumber: $request->boolean('doc_number_manual') ? $request->doc_number : null,
        );

        return redirect()->route('documents.edit', $document)
            ->with('status', "Draft dibuat dengan nomor {$document->doc_number}. Lanjutkan pengisian.");
    }

    /** 2-step wizard — renders the current step's fields from the schema (PRD v2 §4). */
    public function edit(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        $schema = SchemaService::for($document->type);

        // Reviewer feedback stays visible during revision (§3.3).
        $annotations = $document->reviews()->with('annotations')->get()
            ->flatMap->annotations->groupBy('section_key');

        return view('documents.edit', [
            'document' => $document,
            'schema' => $schema,
            'contentMap' => $document->contentMap(),
            'editable' => $this->isEditable($document, $request),
            'candidates' => $this->userPickerCandidates($schema),
            'userValues' => $this->userPickerValues($document),
            'annotations' => $annotations,
        ]);
    }

    /** "Dokumen Berlaku" — dokumen published/sedang_direvisi (untuk Ajukan Revisi Tipe B). */
    public function published(Request $request)
    {
        $user = $request->user();

        $query = Document::with('type', 'department', 'creator')
            ->whereIn('status', ['published', 'sedang_direvisi'])
            ->when($request->filled('type'), fn ($q) => $q->whereHas('type', fn ($t) => $t->where('code', strtoupper($request->type))))
            ->latest('published_at');

        if (! $user->can('document.view_all')) {
            $query->where('department_id', $user->department_id);
        }

        return view('documents.berlaku', [
            'documents' => $query->paginate(15)->withQueryString(),
            'filterType' => $request->type,
        ]);
    }

    /** Ajukan Revisi (Tipe B) — buat versi baru dari dokumen Berlaku. */
    public function requestRevision(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->can('document.request_revision'), 403);
        abort_unless($document->status === 'published', 422, 'Hanya dokumen Berlaku yang dapat direvisi.');

        $new = $this->documents->requestRevision($document, $request->user());

        return redirect()->route('documents.edit', $new)
            ->with('status', "Revisi ke-{$new->no_revisi} dibuat. Versi lama kini 'Sedang Direvisi'. Perbarui isi lalu kirim.");
    }

    /** "Dokumen Revisi" — dokumen ditolak milik user (Revisi Tipe A, §3.3). */
    public function revisi(Request $request)
    {
        $user = $request->user();

        $documents = Document::with('type', 'department', 'reviews.annotations')
            ->where('status', 'rejected')
            ->where(fn ($q) => $q->where('created_by', $user->id)->orWhere('reviewer_id', $user->id))
            ->when(! $user->can('document.view_all'), fn ($q) => $q)
            ->latest('updated_at')
            ->paginate(15);

        return view('documents.revisi', ['documents' => $documents]);
    }

    /**
     * Persist one wizard step. Handles Back / Langkah Berikutnya / Simpan / Kirim.
     * Content sections go to value_json; user_picker sections map to the
     * reviewer/approver columns and document_authors.
     */
    public function saveStep(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeView($request, $document);
        abort_unless($this->isEditable($document, $request), 403, 'Dokumen tidak dapat diedit pada status ini.');

        $schema = SchemaService::for($document->type);
        $step = (int) $request->input('step', $document->current_step);
        $action = $request->input('action', 'next');

        $this->persistStep($request, $schema, $step, $document);

        // Kirim: validate the flow participants then move to review.
        if ($action === 'submit') {
            if (! $document->reviewer_id || ! $document->approver_id) {
                return back()->with('error', 'Peninjau dan Penyetuju wajib dipilih sebelum mengirim.');
            }

            // Resubmitting a rejected doc is a new revision round (Tipe A).
            if ($document->status === 'rejected') {
                $document->revision_round++;
            }

            $document->status = 'in_review';
            $document->submitted_at = now();
            $document->save();

            app(\App\Services\AuditService::class)->log('document.submit', $document->id, ['status' => 'in_review', 'round' => $document->revision_round]);
        $document->reviewer?->notify(new \App\Notifications\DocumentNotification(
            $document, "Dokumen {$document->doc_number} perlu ditinjau.", 'bi-clipboard-check', 'review.index'
        ));

            return redirect()->route('documents.index')->with('status', "Dokumen {$document->doc_number} dikirim untuk ditinjau.");
        }

        // Simpan: keep as draft, return to index.
        if ($action === 'save') {
            $document->save();

            return redirect()->route('documents.index')->with('status', "Dokumen {$document->doc_number} disimpan sebagai draft.");
        }

        // Back / Next navigation.
        $target = $action === 'back' ? $step - 1 : $step + 1;
        $document->current_step = max(1, min($target, $schema->stepCount()));
        $document->save();

        return redirect()->route('documents.edit', $document)->with('status', "Langkah {$step} tersimpan.");
    }

    /** Autosave (D7) — persists text sections for the current step without navigating. */
    public function autosave(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        if (! $this->isEditable($document, $request)) {
            return response()->json(['ok' => false], 422);
        }

        $schema = SchemaService::for($document->type);
        $step = (int) $request->input('step', $document->current_step);
        $sections = $request->input('sections', []);

        foreach ($schema->sectionsForStep($step) as $section) {
            if (($section['type'] ?? '') === 'user_picker') {
                continue; // relationship-backed, saved on step submit
            }
            $this->documents->saveSection($document, $section['key'], $this->cleanValue($section['type'] ?? 'text', $sections[$section['key']] ?? null));
        }

        return response()->json(['ok' => true, 'saved_at' => now()->format('H:i:s')]);
    }

    /** Save every section of a step (content, uploads, and flow participants). */
    private function persistStep(Request $request, SchemaService $schema, int $step, Document $document): void
    {
        $sections = $request->input('sections', []);
        $this->applyUploads($request, $schema, $step, $document, $sections);

        foreach ($schema->sectionsForStep($step) as $section) {
            $key = $section['key'];
            $type = $section['type'] ?? 'text';

            if ($type === 'user_picker') {
                $this->saveParticipants($document, $key, $sections[$key] ?? null);

                continue;
            }

            $this->documents->saveSection($document, $key, $this->cleanValue($type, $sections[$key] ?? null));
        }

        $document->save();
    }

    /** Map user_picker sections to reviewer/approver columns and document_authors. */
    private function saveParticipants(Document $document, string $key, mixed $value): void
    {
        if ($key === 'peninjau') {
            $document->reviewer_id = $value ?: null;
        } elseif ($key === 'penyetuju') {
            $document->approver_id = $value ?: null;
        } elseif ($key === 'pembuat_tambahan') {
            $ids = collect(is_array($value) ? $value : [])
                ->filter()->map(fn ($v) => (int) $v)->unique()
                ->reject(fn ($id) => $id === $document->created_by);

            $document->authors()->where('is_primary', false)->delete();
            foreach ($ids as $id) {
                $document->authors()->create(['user_id' => $id, 'is_primary' => false]);
            }
        }
    }

    /** Trim empty items from list/group values so blank rows aren't persisted. */
    private function cleanValue(string $type, mixed $value): mixed
    {
        if (in_array($type, ['rich_list', 'reference_picker'], true) && is_array($value)) {
            return array_values(array_filter($value, fn ($v) => filled(trim((string) $v))));
        }

        if ($type === 'repeatable_group' && is_array($value)) {
            return array_values(array_filter($value, fn ($row) => is_array($row) && collect($row)->filter(fn ($v) => filled($v))->isNotEmpty()));
        }

        return $value;
    }

    /**
     * Store uploaded images (image / text_or_image group fields) per department
     * (PRD v2 §4.3: storage/app/public/lampiran/{DEPT}/{JENIS}/) and inject the
     * resulting path into $sections so it persists in value_json.
     */
    private function applyUploads(Request $request, SchemaService $schema, int $step, Document $document, array &$sections): void
    {
        $files = $request->file('files', []);
        $dept = $document->department->code;
        $jenis = $document->type->code;

        foreach ($schema->sectionsForStep($step) as $section) {
            if (($section['type'] ?? null) !== 'repeatable_group') {
                continue;
            }

            $imageKeys = collect($section['group_fields'] ?? $section['fields'] ?? [])
                ->filter(fn ($f) => in_array($f['type'] ?? null, ['image', 'text_or_image'], true))
                ->pluck('key');

            $rowFiles = $files[$section['key']] ?? [];

            foreach ($rowFiles as $rowIndex => $fieldFiles) {
                foreach ($fieldFiles as $fieldKey => $uploaded) {
                    if (! $uploaded || ! $uploaded->isValid() || ! $imageKeys->contains($fieldKey)) {
                        continue;
                    }

                    // Guard: JPG/PNG, max 2MB (PRD §4.3).
                    if (! in_array($uploaded->getMimeType(), ['image/jpeg', 'image/png'], true) || $uploaded->getSize() > 2 * 1024 * 1024) {
                        continue;
                    }

                    $filename = uniqid('img_').'.'.$uploaded->getClientOriginalExtension();
                    $path = $uploaded->storeAs("lampiran/{$dept}/{$jenis}", $filename, 'public');

                    $sections[$section['key']][$rowIndex][$fieldKey] = $path;

                    $document->attachments()->create([
                        'section_key' => $section['key'],
                        'path' => $path,
                        'original_name' => $uploaded->getClientOriginalName(),
                        'mime' => $uploaded->getMimeType(),
                        'size' => $uploaded->getSize(),
                    ]);
                }
            }
        }
    }

    private function isEditable(Document $document, Request $request): bool
    {
        $ownerOrAdmin = $document->created_by === $request->user()->id || $request->user()->can('document.view_all');

        // draft = normal editing; rejected = directed revision (Tipe A, §3.3).
        return $ownerOrAdmin && in_array($document->status, ['draft', 'rejected', 'needs_revision'], true);
    }

    /** Candidate users for each user_picker section (filtered by role_filter). */
    private function userPickerCandidates(SchemaService $schema): array
    {
        $out = [];

        foreach ($schema->allSections() as $section) {
            if (($section['type'] ?? null) !== 'user_picker') {
                continue;
            }

            $query = \App\Models\User::with('department')->where('status', 'active');

            if ($filter = ($section['role_filter'] ?? null)) {
                $query->whereHas('roles', fn ($q) => $q->whereIn('name', $filter));
            }

            $out[$section['key']] = $query->orderBy('name')->get();
        }

        return $out;
    }

    /** Current values for user_picker sections (reviewer/approver/co-authors). */
    private function userPickerValues(Document $document): array
    {
        return [
            'peninjau' => $document->reviewer_id,
            'penyetuju' => $document->approver_id,
            'pembuat_tambahan' => $document->authors()->where('is_primary', false)->pluck('user_id')->all(),
        ];
    }

    /** Kirim dari index — draft menjadi in_review bila peninjau & penyetuju sudah dipilih. */
    public function submit(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeView($request, $document);
        // draft = kirim awal; rejected = kirim ulang setelah revisi (Tipe A).
        abort_unless($this->isEditable($document, $request) && in_array($document->status, ['draft', 'rejected'], true), 403);

        if (! $document->reviewer_id || ! $document->approver_id) {
            return redirect()->route('documents.edit', $document)
                ->with('error', 'Lengkapi Peninjau dan Penyetuju di Langkah 2 sebelum mengirim.');
        }

        // Resubmitting a rejected document starts a new revision round.
        if ($document->status === 'rejected') {
            $document->revision_round++;
        }

        $document->status = 'in_review';
        $document->submitted_at = now();
        $document->save();
        app(\App\Services\AuditService::class)->log('document.submit', $document->id, ['status' => 'in_review', 'round' => $document->revision_round]);
        $document->reviewer?->notify(new \App\Notifications\DocumentNotification(
            $document, "Dokumen {$document->doc_number} perlu ditinjau.", 'bi-clipboard-check', 'review.index'
        ));

        return redirect()->route('documents.index')->with('status', "Dokumen {$document->doc_number} dikirim untuk ditinjau.");
    }

    /** Hapus (soft delete) — hanya draft milik sendiri / admin. */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeView($request, $document);
        abort_unless($this->isEditable($document, $request) && $document->status === 'draft', 403, 'Hanya draft yang bisa dihapus.');

        $document->delete();
        app(\App\Services\AuditService::class)->log('document.delete', $document->id);

        return redirect()->route('documents.index')->with('status', 'Draft dihapus.');
    }

    /** PDF cetak (DomPDF) — inline di tab baru. Format mengikuti SOP contoh. */
    public function pdf(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        return Pdf::loadView('documents.print.render', $this->printData($document))
            ->setPaper('a4')
            ->stream($document->doc_number.'.pdf');
    }

    /** Preview 1:1 (HTML) — memakai partial cetak yang sama, ditampilkan di modal. */
    public function preview(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        return view('documents.print.render', $this->printData($document));
    }

    /**
     * View data for the print/preview templates. Images (logo + lampiran) are
     * embedded as base64 data URIs so they render both in DomPDF and the browser.
     */
    private function printData(Document $document): array
    {
        $document->load('creator', 'reviewer', 'approver', 'contents', 'reviews');

        $logoPath = public_path('images/logo-ppa.png');
        $logo = is_file($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        $embed = function (?string $path) {
            if (! $path) {
                return null;
            }
            $full = storage_path('app/public/'.$path);

            return is_file($full)
                ? 'data:'.mime_content_type($full).';base64,'.base64_encode(file_get_contents($full))
                : null;
        };

        return [
            'document' => $document,
            'schema' => SchemaService::for($document->type),
            'contentMap' => $document->contentMap(),
            'logo' => $logo,
            'embed' => $embed,
        ];
    }

    /** Strict per-department visibility (D12). */
    private function authorizeView(Request $request, Document $document): void
    {
        $user = $request->user();

        $allowed = $user->can('document.view_all')
            || $document->created_by === $user->id
            || $document->department_id === $user->department_id;

        abort_unless($allowed, 403, 'Anda tidak memiliki akses ke dokumen ini.');
    }
}
