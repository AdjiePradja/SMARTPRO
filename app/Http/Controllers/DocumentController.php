<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\DocumentParticipantResolver;
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

        // GL "Dokumen Saya" (submenu): SELURUH dokumen BUATANNYA SENDIRI, segala
        // status — agar cocok dgn kartu dashboard "Dokumen Saya" (hapus tetap hanya
        // di draft). PJO/Admin & Non-Staff: hanya yang SEDANG BERPROSES (Berlaku ada
        // di menu "Dokumen Berlaku").
        $glOwnScope = ! $user->can('document.view_all') && $user->can('document.create');

        $query = Document::with('type', 'department', 'creator')->latest();

        if ($glOwnScope) {
            // "Dokumen Saya": semua buatannya KECUALI versi lama yg SEDANG DIREVISI
            // (sudah digantikan draft revisinya) & yg OBSOLETE (ada di Tidak Berlaku).
            $query->where('created_by', $user->id)
                ->whereNotIn('status', ['sedang_direvisi', 'obsolete']);
        } else {
            $query->whereNotIn('status', ['published', 'sedang_direvisi', 'obsolete']);
            // PJO/Admin lihat 7 dept (+filter dept); Non-Staff read-only se-departemen.
            if ($user->can('document.view_all')) {
                if ($dept = $request->input('department_id')) {
                    $query->where('department_id', $dept);
                }
            } else {
                $query->where('department_id', $user->department_id);
            }
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
            'filters' => $request->only('q', 'status', 'type', 'department_id'),
            'types' => \App\Models\DocumentType::orderBy('code')->pluck('code'),
            'departments' => $user->can('document.view_all') ? Department::orderBy('code')->get() : collect(),
            // GL "Dokumen Saya" memuat segala status → filter status tampilkan semua.
            'showAllStatuses' => $glOwnScope,
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

    /** Halaman detail dokumen + timeline vertikal riwayat dari audit_logs (v3.1 §9). */
    public function show(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        $timeline = \App\Models\AuditLog::where('document_id', $document->id)
            ->with('user')->orderBy('created_at')->get();

        return view('documents.show', [
            'document' => $document->load('creator', 'reviewer', 'approver', 'department', 'type'),
            'timeline' => $timeline,
        ]);
    }

    /** 2-step wizard — renders the current step's fields from the schema (PRD v2 §4). */
    public function edit(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        $schema = SchemaService::for($document->type);
        $editable = $this->isEditable($document, $request);

        // Draft revisi Tipe B punya SATU langkah ekstra di akhir: form log revisi
        // (lembar CATATAN REVISI). Simpan/Kirim pindah ke langkah itu.
        $totalSteps = $schema->stepCount() + ($document->isRevisionDraft() ? 1 : 0);

        // Read-only viewers navigate via ?view_step (no DB write) — cegah bug 403
        // saat tombol Kembali di dokumen non-draft (v3.1 §6 bug fix).
        $currentStep = $editable
            ? $document->current_step
            : (int) $request->query('view_step', $document->current_step);
        $currentStep = max(1, min($currentStep, $totalSteps));

        // Reviewer feedback stays visible during revision (§3.3). Dua lapis:
        // rangkuman (summary) + anotasi per-item.
        $reviews = $document->reviews()->with('annotations')->latest()->get();
        $annotations = $reviews->flatMap->annotations->groupBy('section_key');
        $reviewSummary = $reviews->firstWhere('decision', 'needs_revision')?->summary;

        return view('documents.edit', [
            'document' => $document,
            'schema' => $schema,
            'currentStep' => $currentStep,
            'totalSteps' => $totalSteps,
            'contentMap' => $document->contentMap(),
            'editable' => $editable,
            'candidates' => $this->userPickerCandidates($schema, $document),
            'userValues' => $this->userPickerValues($document),
            'annotations' => $annotations,
            'reviewSummary' => $reviewSummary,
        ]);
    }

    /**
     * "Status Dokumen Staff" — read-only (v3.1 §3.3). GL & Section Head melihat
     * status dokumen STAFF di departemennya. Tanpa hak edit/kirim/hapus.
     */
    public function staffStatus(Request $request)
    {
        $user = $request->user();
        $canAll = $user->can('document.view_all');
        abort_unless(
            $canAll || in_array($user->jabatan, [User::JABATAN_GROUP_LEADER, User::JABATAN_SECTION_HEAD, User::JABATAN_DEPARTEMEN_HEAD], true),
            403
        );

        // PJO/Admin: pilih departemen (submenu 7 dept, v2 2e); GL/SH/DH: dept sendiri (2d).
        $deptId = $canAll ? ($request->input('department_id') ?: null) : $user->department_id;

        $query = Document::with('type', 'department', 'creator')
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->when($request->filled('type'), fn ($q) => $q->whereHas('type', fn ($t) => $t->where('code', strtoupper($request->type))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('doc_number', 'like', "%{$request->q}%")->orWhere('title', 'like', "%{$request->q}%")))
            ->latest();

        return view('documents.staff-status', [
            'documents' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only('q', 'status', 'type', 'department_id'),
            'types' => \App\Models\DocumentType::orderBy('code')->pluck('code'),
            'departments' => $canAll ? Department::orderBy('code')->get() : collect(),
            'canAll' => $canAll,
            'selectedDept' => $deptId ? Department::find($deptId) : null,
        ]);
    }

    /** "Dokumen Berlaku" — dokumen published/sedang_direvisi (untuk Ajukan Revisi Tipe B). */
    public function published(Request $request)
    {
        $user = $request->user();

        // v2 2g: SH/GL/DH lihat dokumen Berlaku dept sendiri; PJO/Admin lihat 7 dept.
        $canAll = $user->can('document.view_all');

        $query = Document::with('type', 'department', 'creator')
            ->whereIn('status', ['published', 'sedang_direvisi'])
            ->when($request->filled('type'), fn ($q) => $q->whereHas('type', fn ($t) => $t->where('code', strtoupper($request->type))))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('doc_number', 'like', "%{$request->q}%")->orWhere('title', 'like', "%{$request->q}%")))
            ->when($canAll && $request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->latest('published_at');

        if (! $canAll) {
            $query->where('department_id', $user->department_id);
        }

        return view('documents.berlaku', [
            'documents' => $query->paginate(15)->withQueryString(),
            'filterType' => $request->type,
            'filters' => $request->only('q', 'type', 'department_id'),
            'departments' => $canAll ? Department::orderBy('code')->get() : collect(),
        ]);
    }

    /** Ajukan Revisi (Tipe B) — buat versi baru dari dokumen Berlaku. */
    public function requestRevision(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->can('document.request_revision'), 403);
        abort_unless($document->status === 'published', 422, 'Hanya dokumen Berlaku yang dapat direvisi.');

        $new = $this->documents->requestRevision($document, $request->user());

        // Pengaju (SH/DH/PJO) TIDAK dibawa ke form edit — draft revisi milik
        // PEMBUAT (GL): muncul kembali di "Status Dokumen"-nya, dinotifikasi,
        // lalu alur review→approve diulang dari awal.
        $new->creator?->notify(new \App\Notifications\DocumentNotification(
            $new, "Dokumen {$new->doc_number} diajukan revisi (Edisi {$new->edisi} Rev {$new->no_revisi}) — silakan perbarui lalu kirim ulang.", 'bi-arrow-repeat', 'documents.index'
        ));

        return back()->with('status', "Revisi {$new->displayNumber()} (Edisi {$new->edisi} Rev {$new->no_revisi}) dibuat & dikembalikan ke pembuat ({$new->creator->name}). Versi lama tetap Berlaku sampai revisi disahkan.");
    }

    /**
     * Batalkan Revisi Tipe B (v3.1 §4.3): dokumen "Sedang Direvisi" batal
     * diperbarui → versi lama kembali Berlaku, versi baru (belum terbit) dibuang.
     */
    public function cancelRevisionB(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->can('document.request_revision'), 403);
        abort_unless($document->status === 'sedang_direvisi', 422, 'Hanya dokumen yang sedang direvisi yang dapat dibatalkan.');

        \Illuminate\Support\Facades\DB::transaction(function () use ($document) {
            // Versi baru = dokumen bernomor sama yang belum terbit. JANGAN cari via
            // no_revisi + 1 — roll-over edisi membuat revisi berikutnya bernomor 0.
            $new = Document::where('doc_number', $document->doc_number)
                ->where('id', '!=', $document->id)
                ->whereIn('status', ['draft', 'waiting_for_review', 'in_review', 'rejected'])
                ->latest('id')->first();

            $document->update(['status' => 'published']);   // versi lama kembali Berlaku
            $new?->delete();                                 // buang versi baru (belum terbit)
        });

        app(\App\Services\AuditService::class)->log('document.cancel_revision_b', $document->id);

        return back()->with('status', "Revisi {$document->displayNumber()} dibatalkan; versi lama kembali Berlaku.");
    }

    /**
     * "Dokumen Revisi" — dokumen yang perlu DIREVISI pembuat:
     * (a) Tipe A: ditolak peninjau/approver; (b) Tipe B: draft revisi dari
     * pengajuan revisi SH/DH/PJO atas dokumen Berlaku (revises_document_id).
     */
    public function revisi(Request $request)
    {
        $user = $request->user();

        // Hanya PEMBUAT yang melihat dokumennya di sini. Peninjau memantau lewat
        // Tinjau Dokumen → Status Revisi (v3.1 §0/§4.2), bukan menu ini.
        $documents = Document::with('type', 'department', 'reviews.annotations')
            ->where(function ($q) {
                $q->where('status', 'rejected')
                    ->orWhere(fn ($w) => $w->whereNotNull('revises_document_id')->where('status', 'draft'));
            })
            ->when(! $user->can('document.view_all'), fn ($q) => $q->where('created_by', $user->id))
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
        $totalSteps = $schema->stepCount() + ($document->isRevisionDraft() ? 1 : 0);
        $step = (int) $request->input('step', $document->current_step);
        $action = $request->input('action', 'next');

        // Langkah virtual (log revisi) berada DI LUAR schema — dipersist terpisah.
        if ($step > $schema->stepCount()) {
            $this->persistRevisionLog($request, $document);
        } else {
            $this->persistStep($request, $schema, $step, $document);
        }

        // Kirim: validate the flow participants then move to review.
        if ($action === 'submit') {
            if (! $document->reviewer_id || ! $document->approver_id) {
                return back()->with('error', 'Peninjau dan Penyetuju wajib dipilih sebelum mengirim.');
            }

            // Peninjau & penyetuju harus sesuai matriks (jenis + dept + SHE/Plant).
            $resolver = app(DocumentParticipantResolver::class);
            if (! $resolver->isValidReviewer($document, $document->reviewer_id) || ! $resolver->isValidApprover($document, $document->approver_id)) {
                return back()->with('error', 'Peninjau/Penyetuju tidak sesuai aturan untuk jenis & departemen dokumen ini.');
            }

            // Resubmitting a rejected doc is a new revision round (Tipe A).
            if ($document->status === 'rejected') {
                $document->revision_round++;
            }

            // waiting_for_review (BUKAN in_review): GL masih bisa MENARIK dokumen
            // selama peninjau belum membukanya (review.show → in_review).
            $document->status = 'waiting_for_review';
            $document->submitted_at = now();
            $document->save();

            app(\App\Services\AuditService::class)->log('document.submit', $document->id, ['status' => 'waiting_for_review', 'round' => $document->revision_round]);
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
        $document->current_step = max(1, min($target, $totalSteps));
        $document->save();

        // Preview tetap terbawa antar langkah (revisi #2) — panel tidak kosong.
        return redirect()->route('documents.edit', [$document, 'preview' => 1])->with('status', "Langkah {$step} tersimpan.");
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

        // Langkah virtual "Log Revisi" (di luar schema) — autosave tersendiri.
        if ($step > $schema->stepCount() && $document->isRevisionDraft()) {
            $this->persistRevisionLog($request, $document);

            return response()->json(['ok' => true, 'saved_at' => now()->format('H:i').' WITA']);
        }

        foreach ($schema->sectionsForStep($step) as $section) {
            if (($section['type'] ?? '') === 'user_picker') {
                continue; // relationship-backed, saved on step submit
            }
            $this->documents->saveSection($document, $section['key'], $this->cleanValue($section['type'] ?? 'text', $sections[$section['key']] ?? null));
        }

        return response()->json(['ok' => true, 'saved_at' => now()->format('H:i').' WITA']);
    }

    /**
     * Unggah satu foto lampiran secara langsung (AJAX) saat dipilih, lalu
     * kembalikan path tersimpan. Path itu disimpan di hidden input pada form,
     * sehingga Preview cukup memuat ulang iframe (tanpa reload halaman) dan
     * foto tidak hilang saat pindah langkah.
     */
    public function uploadAttachment(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        if (! $this->isEditable($document, $request)) {
            return response()->json(['ok' => false, 'message' => 'Dokumen tidak dapat diedit.'], 422);
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png', 'max:2048'],
            'section' => ['required', 'string'],
        ]);

        $dept = $document->department->code;
        $jenis = $document->type->code;
        $uploaded = $validated['image'];

        $filename = uniqid('img_').'.'.$uploaded->getClientOriginalExtension();
        $path = $uploaded->storeAs("lampiran/{$dept}/{$jenis}", $filename, 'public');

        $document->attachments()->create([
            'section_key' => $validated['section'],
            'path' => $path,
            'original_name' => $uploaded->getClientOriginalName(),
            'mime' => $uploaded->getMimeType(),
            'size' => $uploaded->getSize(),
        ]);

        return response()->json(['ok' => true, 'path' => $path, 'url' => \Illuminate\Support\Facades\Storage::url($path)]);
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

    /**
     * Persist langkah virtual "Log Revisi" (hanya draft revisi Tipe B):
     * baris catatan perubahan per halaman (lembar CATATAN REVISI) + override
     * manual Edisi/Revisi. Baris lama (salinan revisi sebelumnya) membawa
     * no_rev-nya sendiri; baris baru diberi no_revisi dokumen ini.
     */
    private function persistRevisionLog(Request $request, Document $document): void
    {
        // Edisi & Revisi: otomatis terisi (roll-over), boleh diedit manual (owner rev).
        $document->edisi = (string) max(1, (int) $request->input('edisi', $document->edisi ?: 1));
        $document->no_revisi = max(0, (int) $request->input('no_revisi', $document->no_revisi));

        $rows = collect($request->input('sections.catatan_revisi', []))
            ->filter(fn ($r) => is_array($r) && collect($r)->only(['tanggal', 'halaman', 'catatan'])->filter(fn ($v) => filled($v))->isNotEmpty())
            ->map(fn ($r) => [
                'no_rev' => filled($r['no_rev'] ?? null) ? (int) $r['no_rev'] : $document->no_revisi,
                'tanggal' => trim((string) ($r['tanggal'] ?? '')),
                'halaman' => trim((string) ($r['halaman'] ?? '')),
                'catatan' => trim((string) ($r['catatan'] ?? '')),
            ])
            ->values()
            ->all();

        $this->documents->saveSection($document, 'catatan_revisi', $rows);
        $document->save();
    }

    /** Map user_picker sections to reviewer/approver columns and document_authors. */
    private function saveParticipants(Document $document, string $key, mixed $value): void
    {
        if ($key === 'peninjau') {
            $document->reviewer_id = $value ?: null;
        } elseif ($key === 'penyetuju') {
            $document->approver_id = $value ?: null;
        } elseif ($key === 'peninjau_penyetuju') {
            // SP/IK: satu SH/DH meninjau SEKALIGUS menyetujui → isi kedua kolom sama.
            $document->reviewer_id = $value ?: null;
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

        // JSA analisa: nested Langkah Kerja → Bahaya → Pengendalian. Buang yang kosong.
        if ($type === 'jsa_analysis' && is_array($value)) {
            $steps = [];
            foreach ($value as $step) {
                if (! is_array($step)) {
                    continue;
                }
                $langkah = trim((string) ($step['langkah'] ?? ''));
                $bahayaList = [];
                foreach (($step['bahaya'] ?? []) as $bahaya) {
                    if (! is_array($bahaya)) {
                        continue;
                    }
                    $risiko = trim((string) ($bahaya['risiko'] ?? ''));
                    $kendali = array_values(array_filter(
                        array_map(fn ($v) => trim((string) $v), (array) ($bahaya['pengendalian'] ?? [])),
                        fn ($v) => $v !== '',
                    ));
                    if ($risiko !== '' || $kendali) {
                        $bahayaList[] = ['risiko' => $risiko, 'pengendalian' => $kendali];
                    }
                }
                if ($langkah !== '' || $bahayaList) {
                    $steps[] = ['langkah' => $langkah, 'bahaya' => $bahayaList];
                }
            }

            return $steps;
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
        $user = $request->user();

        // Hanya pihak yang boleh MEMBUAT/menyunting dokumen (GL/Admin). Non-Staff
        // read-only tak bisa menyunting draft lama miliknya (Fase B).
        if (! $user->can('document.create')) {
            return false;
        }

        $ownerOrAdmin = $document->created_by === $user->id || $user->can('document.view_all');

        // draft = normal editing; rejected = directed revision (Tipe A, §3.3).
        return $ownerOrAdmin && in_array($document->status, ['draft', 'rejected', 'needs_revision'], true);
    }

    /**
     * Kandidat user_picker. Peninjau & penyetuju memakai matriks aturan-alur-v2
     * (jenis + departemen + SHE/Plant); section lain memakai role_filter schema.
     */
    private function userPickerCandidates(SchemaService $schema, Document $document): array
    {
        $resolver = app(DocumentParticipantResolver::class);
        $out = [];

        foreach ($schema->allSections() as $section) {
            if (($section['type'] ?? null) !== 'user_picker') {
                continue;
            }

            $key = $section['key'];
            if ($key === 'peninjau' || $key === 'peninjau_penyetuju') {
                // peninjau_penyetuju (SP/IK): kandidat = SH/DH dept (peninjau &
                // penyetuju identik untuk alur ini).
                $out[$key] = $resolver->reviewerCandidates($document);

                continue;
            }
            if ($key === 'penyetuju') {
                $out[$key] = $resolver->approverCandidates($document);

                continue;
            }

            $query = \App\Models\User::with('department')->where('status', 'active');
            if ($filter = ($section['role_filter'] ?? null)) {
                $query->whereHas('roles', fn ($q) => $q->whereIn('name', $filter));
            }
            $out[$key] = $query->orderBy('name')->get();
        }

        return $out;
    }

    /** Current values for user_picker sections (reviewer/approver/co-authors). */
    private function userPickerValues(Document $document): array
    {
        return [
            'peninjau' => $document->reviewer_id,
            'penyetuju' => $document->approver_id,
            'peninjau_penyetuju' => $document->reviewer_id,   // SP/IK: sama dgn approver
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

        // Peninjau & penyetuju harus sesuai matriks (jenis + dept + SHE/Plant).
        $resolver = app(DocumentParticipantResolver::class);
        if (! $resolver->isValidReviewer($document, $document->reviewer_id) || ! $resolver->isValidApprover($document, $document->approver_id)) {
            return redirect()->route('documents.edit', $document)
                ->with('error', 'Peninjau/Penyetuju tidak sesuai aturan untuk jenis & departemen dokumen ini.');
        }

        // Resubmitting a rejected document starts a new revision round.
        if ($document->status === 'rejected') {
            $document->revision_round++;
        }

        $document->status = 'waiting_for_review';
        $document->submitted_at = now();
        $document->save();
        app(\App\Services\AuditService::class)->log('document.submit', $document->id, ['status' => 'waiting_for_review', 'round' => $document->revision_round]);
        $document->reviewer?->notify(new \App\Notifications\DocumentNotification(
            $document, "Dokumen {$document->doc_number} perlu ditinjau.", 'bi-clipboard-check', 'review.index'
        ));

        return redirect()->route('documents.index')->with('status', "Dokumen {$document->doc_number} dikirim untuk ditinjau.");
    }

    /** Tarik (withdraw) — hanya saat waiting_for_review (belum disentuh peninjau) → kembali draft (v3.1 §4.1). */
    public function withdraw(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeView($request, $document);
        abort_unless($document->created_by === $request->user()->id || $request->user()->can('document.view_all'), 403);
        abort_unless($document->status === 'waiting_for_review', 403, 'Hanya dokumen yang menunggu tinjauan yang bisa ditarik.');

        $document->update(['status' => 'draft']);
        app(\App\Services\AuditService::class)->log('document.withdraw', $document->id);

        return redirect()->route('documents.index')->with('status', "Dokumen {$document->doc_number} ditarik kembali ke draft.");
    }

    /** Hapus (soft delete) — hanya draft milik sendiri / admin. */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeView($request, $document);

        // Draft → pemilik/admin; Tidak Berlaku (obsolete) → yang berhak kelola revisi
        // (cleanup dokumen lama, v2 Fase D).
        $draftOwner = $this->isEditable($document, $request) && $document->status === 'draft';
        $obsoleteCleaner = $document->status === 'obsolete' && $request->user()->can('document.request_revision');
        abort_unless($draftOwner || $obsoleteCleaner, 403, 'Hanya draft (pemilik) atau dokumen Tidak Berlaku yang bisa dihapus.');

        $back = $document->status === 'obsolete' ? 'documents.obsolete' : 'documents.index';
        $document->delete();
        app(\App\Services\AuditService::class)->log('document.delete', $document->id);

        return redirect()->route($back)->with('status', 'Dokumen dihapus.');
    }

    /** Jadikan Tidak Berlaku (obsolete) — dokumen Berlaku dinonaktifkan (v2 Fase D). */
    public function makeObsolete(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->can('document.request_revision'), 403);
        abort_unless($document->status === 'published', 422, 'Hanya dokumen Berlaku yang dapat dinonaktifkan.');

        $document->update(['status' => 'obsolete']);
        app(\App\Services\AuditService::class)->log('document.make_obsolete', $document->id, ['from' => 'published']);

        return back()->with('status', "Dokumen {$document->displayNumber()} kini Tidak Berlaku.");
    }

    /** "Dokumen Tidak Berlaku" (obsolete) — daftar + opsi hapus (v2 Fase D).
     *  GL boleh MELIHAT (read-only, dept sendiri); hapus tetap SH/DH/PJO/Admin. */
    public function obsolete(Request $request)
    {
        $user = $request->user();
        abort_unless($user->can('document.request_revision') || $user->can('document.view_all') || $user->can('document.create'), 403);
        $canAll = $user->can('document.view_all');

        $query = Document::with('type', 'department', 'creator')
            ->where('status', 'obsolete')
            ->when(! $canAll, fn ($q) => $q->where('department_id', $user->department_id))
            ->when($canAll && $request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('doc_number', 'like', "%{$request->q}%")->orWhere('title', 'like', "%{$request->q}%")))
            ->latest('updated_at');

        return view('documents.obsolete', [
            'documents' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only('q', 'department_id'),
            'departments' => $canAll ? Department::orderBy('code')->get() : collect(),
        ]);
    }

    /** PDF cetak (DomPDF) — inline di tab baru. View & orientasi dari schema jenis. */
    public function pdf(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        $dompdf = $this->renderPdfDocument($document);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$document->doc_number.'.pdf"',
        ]);
    }

    /**
     * Render PDF + stamp nomor halaman. Terpisah dari pdf() agar test tata letak
     * bisa memakai hasil PDF yang SAMA persis dengan yang diunduh user.
     */
    private function renderPdfDocument(Document $document): \Dompdf\Dompdf
    {
        $data = $this->printData($document);
        $view = $this->printView($document);

        // JSA: mesin paginasi 2-fase (mengulang header Langkah/Bahaya di halaman
        // lanjutan + menutup border per-halaman spt docs/JSA new.docx). Jenis lain
        // (SOP/IK/SP) tetap 1-lintasan.
        if ($view === 'documents.print.render-jsa') {
            return $this->renderJsaPaginated($data, $view);
        }

        return $this->renderStandardPaginated($data, $view);
    }

    /**
     * Render SOP/SP/IK dgn paginasi 2-fase pada tabel AKTIVITAS. Tanpa ini, grup
     * aktivitas yang terpotong antar halaman membuat (a) sel PIC rowspan RUSAK
     * (PIC hilang di halaman lanjutan) dan (b) border tabel MENGGANTUNG (tepi
     * bawah halaman tak tertutup) — krn kelas "sel gabungan" membuang border.
     *   1) Render PROBE "datar" (tanpa rowspan/kelas border) → ukur baris awal
     *      tiap halaman lewat anchor 1pt [[Ai]].
     *   2) plan() men-scope rowspan PIC & kelas border PER HALAMAN, lalu render
     *      ulang → tepi selalu tertutup & PIC diulang di halaman lanjutan.
     * Bila tak ada aktivitas / anchor tak terbaca → fallback 1-lintasan.
     */
    private function renderStandardPaginated(array $data, string $view): \Dompdf\Dompdf
    {
        [$aktKey, $autoNumber] = $this->activitySection($data['schema']);

        $flat = $aktKey !== null
            ? \App\Services\ActivityPrintLayout::flatten((array) ($data['contentMap'][$aktKey] ?? []), $autoNumber)
            : [];

        $starts = [];
        $usePlan = false;
        if ($flat !== []) {
            $usePlan = true;
            $probeRows = \App\Services\ActivityPrintLayout::plan($flat, []);
            // Kop berulang tiap halaman memuat "Dokumen" (No. Dokumen) → penanda halaman.
            $measured = $this->measureRowPageStarts(
                $this->renderStandardOnce($view, $data, $aktKey, $probeRows, true), 'Dokumen', 'A'
            );
            if ($measured === null) {
                $usePlan = false;
            } else {
                $starts = $measured;
            }
        }

        $finalData = $usePlan
            ? array_merge($data, [
                'aktRows' => [$aktKey => \App\Services\ActivityPrintLayout::plan($flat, $starts)],
                'probeFlat' => false,
            ])
            : $data;

        $dompdf = Pdf::loadView($view, $finalData)
            ->setPaper('a4', $data['orientation'])
            ->getDomPDF();
        $dompdf->render();
        $this->stampPageNumbers($dompdf, $data['orientation']);

        return $dompdf;
    }

    /** Satu render SOP/SP/IK dgn baris aktivitas ter-plan (probe = tata letak datar). */
    private function renderStandardOnce(string $view, array $data, ?string $aktKey, array $rows, bool $probeFlat = false): \Dompdf\Dompdf
    {
        $dompdf = Pdf::loadView($view, array_merge($data, [
            'aktRows' => [$aktKey => $rows],
            'probeFlat' => $probeFlat,
        ]))->setPaper('a4', $data['orientation'])->getDomPDF();
        $dompdf->render();

        return $dompdf;
    }

    /**
     * Section "aktivitas" = repeatable_group yang punya field PIC (SOP/SP: bab V,
     * IK: bab I). Mengembalikan [key, auto_number] atau [null, ''].
     */
    private function activitySection(SchemaService $schema): array
    {
        foreach ($schema->allSections() as $section) {
            if (($section['type'] ?? '') !== 'repeatable_group') {
                continue;
            }
            $fields = collect($section['group_fields'] ?? $section['fields'] ?? []);
            if ($fields->contains('key', 'pic')) {
                return [$section['key'], $section['auto_number'] ?? ''];
            }
        }

        return [null, ''];
    }

    /**
     * Render JSA dgn paginasi 2-fase. DomPDF tak bisa mengulang header step di
     * halaman lanjutan & tak mengekspos page-break, jadi:
     *   1) Render → UKUR (anchor 1pt [[Ri]]) baris mana memulai tiap halaman.
     *   2) plan() menampilkan ULANG teks Langkah/Bahaya di baris pertama tiap
     *      segmen-halaman & menutup border (mrg-*), lalu render lagi.
     * Menyisipkan header menggeser paginasi → diulang sampai titik-potong STABIL
     * (cap 5 iterasi). Bila anchor tak terbaca → fallback 1-lintasan (tak pernah
     * lebih buruk dari sebelumnya).
     */
    private function renderJsaPaginated(array $data, string $view): \Dompdf\Dompdf
    {
        $analisa = is_array($data['contentMap']['analisa'] ?? null) ? $data['contentMap']['analisa'] : [];
        $flat = \App\Services\JsaPrintLayout::flatten($analisa);

        // ISI HALAMAN PENUH (v12): rowspan TAK BISA melintasi halaman di DomPDF (sel
        // Langkah/Bahaya jadi KOSONG di lanjutan) → kita scope PER HALAMAN + page-break
        // DIPAKSA agar DomPDF memotong PERSIS di titik yg di-scope. Titik itu dicari agar
        // halaman TERISI PENUH:
        //   SEED — render ALIRAN ALAMI rowspan NYATA tanpa paksa (forceBreaks=false):
        //     DomPDF mengisi & memecah grup sendiri → titik potong alami = fill points.
        //   ITERASI — render FINAL berpaksa di $starts lalu UKUR titik potong NYATA-nya.
        //     Bila scope (Langkah/Bahaya diulang di atas halaman) menambah tinggi hingga
        //     MELUBER, DomPDF menyisipkan potongan EKSTRA → terukur → tambahkan ke
        //     $starts & ulang. Monoton bertambah → konvergen (cap 5) → tak ada sel kosong
        //     & tak meluber.
        // Penanda halaman = HEADER TABEL "Uraian" (berulang via thead); BUKAN "FORMULIR"
        // (kop hanya hal. 1-2). Anchor tak terbaca → fallback 1-lintasan.
        $starts = [];
        $usePlan = false;
        if ($flat !== []) {
            // FASE A — KANDIDAT: iterasi ALIRAN ALAMI ber-scope (forceBreaks=false).
            // DomPDF mengisi halaman penuh & memecah grup → titik potong yg MENGISI penuh.
            // Bisa BEROSILASI ±1 baris (dua tata letak sama valid) → kumpulkan semua
            // kandidat yang muncul.
            $cands = [];
            $probe = [];
            for ($iter = 0; $iter < 4; $iter++) {
                $m = $this->measureRowPageStarts(
                    $this->renderJsaOnce($view, $data, \App\Services\JsaPrintLayout::plan($flat, $probe), false, false), 'Uraian'
                );
                if ($m === null) {
                    break;
                }
                $cands[implode(',', $m)] = $m;
                if ($m === $probe) {
                    break;   // konvergen
                }
                $probe = $m;
            }

            // FASE B — PILIH: kandidat dgn potongan PALING SEDIKIT (halaman paling sedikit)
            // yang STABIL saat DIPAKSA — forced-measured == kandidat berarti scope PERSIS
            // cocok dgn potongan DomPDF (tak meluber, tak ada sel Langkah/Bahaya kosong).
            // Kandidat "terlalu rakus" (satu baris meluber) ditolak; yg lebih banyak
            // potongan hampir selalu stabil → selalu ketemu.
            $list = array_values($cands);
            usort($list, fn ($a, $b) => count($a) <=> count($b));
            foreach ($list as $cand) {
                $m = $this->measureRowPageStarts(
                    $this->renderJsaOnce($view, $data, \App\Services\JsaPrintLayout::plan($flat, $cand), false, true), 'Uraian'
                );
                if ($m === $cand) {
                    $starts = $cand;
                    $usePlan = true;
                    break;
                }
            }
        }

        $finalData = $usePlan
            ? array_merge($data, [
                'analisaRows' => \App\Services\JsaPrintLayout::plan($flat, $starts),
                'probeFlat' => false,
                'forceBreaks' => true,   // FINAL: paksa potong di titik konvergen
            ])
            : $data;
        $dompdf = Pdf::loadView($view, $finalData)
            ->setPaper('a4', $data['orientation'])
            ->getDomPDF();
        $dompdf->render();

        // Kop JSA ada di halaman lembar revisi (hal. 1) + halaman pertama body.
        // Tanpa revisi → body = hal. 1. Dengan revisi → revisi hal. 1, body hal. 2
        // (lembar revisi 1 halaman; page-break-after memaksa body ke halaman berikut).
        $hasRevisi = ! empty(array_filter((array) ($data['contentMap']['catatan_revisi'] ?? [])));
        $this->stampPageNumbers($dompdf, $data['orientation'], $hasRevisi ? [1, 2] : [1]);

        return $dompdf;
    }

    /**
     * Satu render JSA dgn baris ter-plan. $probeFlat=true → tata letak DATAR
     * (tanpa rowspan) khusus pengukuran Fase 1.
     */
    private function renderJsaOnce(string $view, array $data, array $rows, bool $probeFlat = false, bool $forceBreaks = true): \Dompdf\Dompdf
    {
        $dompdf = Pdf::loadView($view, array_merge($data, ['analisaRows' => $rows, 'probeFlat' => $probeFlat, 'forceBreaks' => $forceBreaks]))
            ->setPaper('a4', $data['orientation'])
            ->getDomPDF();
        $dompdf->render();

        return $dompdf;
    }

    /**
     * Ukur, dari anchor 1pt "[[Ri]]" pada stream PDF, indeks baris yang MEMULAI
     * tiap halaman (≥ halaman 2) — dipakai sbg $pageStarts untuk plan(). Halaman
     * dikenali dari kop tetap "FORMULIR" (berulang tiap halaman). Mengembalikan
     * null bila tak ada anchor terbaca (→ fallback 1-lintasan).
     *
     * @return int[]|null indeks baris awal-halaman, menaik.
     */
    private function measureRowPageStarts(\Dompdf\Dompdf $dompdf, string $pageMarker = 'FORMULIR', string $anchorPrefix = 'R'): ?array
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $dompdf->output(), $sm);

        $pageNo = 0;
        $rowPage = [];
        foreach ($sm[1] as $s) {
            $c = @gzuncompress($s);
            if ($c === false) {
                $c = @gzinflate($s);
            }
            if ($c === false) {
                $c = $s;
            }
            // Baca teks: ambil literal (...), buang null (DomPDF menulis UTF-16BE).
            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $c, $lit);
            $txt = '';
            foreach ($lit[0] as $l) {
                $inner = preg_replace('/\\\\([()\\\\])/', '$1', substr($l, 1, -1));
                $txt .= str_replace("\x00", '', $inner);
            }
            if (! str_contains($txt, $pageMarker)) {   // hanya stream halaman nyata
                continue;
            }
            $pageNo++;
            if (preg_match_all('/\[\['.preg_quote($anchorPrefix, '/').'(\d+)\]\]/', $txt, $mm)) {
                foreach ($mm[1] as $idx) {
                    $idx = (int) $idx;
                    if (! isset($rowPage[$idx])) {
                        $rowPage[$idx] = $pageNo;
                    }
                }
            }
        }

        if ($rowPage === []) {
            return null;
        }

        ksort($rowPage);
        $starts = [];
        $prevPage = 1;
        foreach ($rowPage as $idx => $pg) {
            if ($pg > $prevPage) {
                $starts[] = $idx;
                $prevPage = $pg;
            }
        }

        return $starts;
    }

    /**
     * Stamp "Halaman X dari Y" ke sel Halaman pada kop (kop berulang lewat thead,
     * jadi satu koordinat berlaku untuk semua halaman). Sel dibiarkan kosong di
     * HTML; nilainya hanya bisa dihitung DomPDF usai render ({PAGE_NUM}/{PAGE_COUNT}).
     * Koordinat dikalibrasi terhadap posisi teks kop pada stream PDF nyata —
     * berubah bila CSS kop (tinggi baris/kolom) diubah.
     */
    private function stampPageNumbers(\Dompdf\Dompdf $dompdf, string $orientation, array $kopPages = [1]): void
    {
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');

        // Koordinat = sel "Halaman" pada kop, dikalibrasi dari stream PDF nyata;
        // x = kolom NILAI kop. Berubah bila ukuran/rasio kop diubah.
        if ($orientation === 'landscape') {
            // JSA: kop hanya di HALAMAN BERKOP (hal. lembar revisi + hal. pertama body).
            // page_text menulis di SEMUA halaman, jadi dipakai page_script yang tahu
            // nomor halaman → stamp hanya di $kopPages. Halaman analisa lanjutan tanpa
            // kop = tanpa nomor. Koordinat = sel "Halaman" pd kop ramping (kalibrasi).
            $canvas->page_script(function (int $pageNumber, int $pageCount, $pdf) use ($font, $kopPages) {
                if (! in_array($pageNumber, $kopPages, true)) {
                    return;
                }
                $pdf->text(711.0, 59.0, "{$pageNumber} dari {$pageCount}", $font, 7.5, [0, 0, 0]);
            });

            return;
        }

        $canvas->page_text(392.8, 94.5, 'Halaman: {PAGE_NUM} dari {PAGE_COUNT}', $font, 8, [0, 0, 0]);
    }

    /**
     * Preview 1:1 (HTML) — SATU sumber dengan PDF: template & orientasi sama-sama
     * dibaca dari schema jenis (print_view + orientation), jadi preview mustahil
     * beda orientasi dengan PDF-nya.
     */
    public function preview(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        // $screen = true → margin @page diabaikan browser; body diberi padding +
        // lebar kertas sesuai orientasi schema.
        return view($this->printView($document), $this->printData($document, screen: true));
    }

    /** Template cetak jenis dokumen (sumber tunggal utk PDF & preview). */
    private function printView(Document $document): string
    {
        return SchemaService::for($document->type)->raw()['print_view'] ?? 'documents.print.render';
    }

    /**
     * Logo untuk kop: pakai versi kecil yang di-cache (mempercepat DomPDF, karena
     * gambar dirender ulang tiap halaman). Logo asli 285KB -> versi ~170px jauh lebih ringan.
     */
    private function pdfLogoDataUri(): ?string
    {
        $src = public_path('images/logo-ppa.png');
        if (! is_file($src)) {
            return null;
        }

        $cache = public_path('images/logo-ppa-pdf.png');
        if (function_exists('imagecreatefrompng') && (! is_file($cache) || filemtime($cache) < filemtime($src))) {
            $img = @imagecreatefrompng($src);
            if ($img) {
                $small = imagescale($img, 170);
                imagesavealpha($small, true);
                imagepng($small, $cache, 8);
                imagedestroy($img);
                imagedestroy($small);
            }
        }

        $use = is_file($cache) ? $cache : $src;

        return 'data:image/png;base64,'.base64_encode(file_get_contents($use));
    }

    /**
     * Cap APPROVED — pakai berkas public/images/approve-stamp.png APA ADANYA
     * (tanpa diproses/di-edit; permintaan pemilik). Ganti berkas itu untuk
     * mengubah tampilan/kemiringan stempel.
     */
    private function pdfStampDataUri(): ?string
    {
        foreach (['images/approve-stamp.png', 'images/approve.png'] as $rel) {
            $path = public_path($rel);
            if (is_file($path)) {
                return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
            }
        }

        return null;
    }

    /**
     * View data for the print/preview templates. Images (logo + lampiran) are
     * embedded as base64 data URIs so they render both in DomPDF and the browser.
     */
    private function printData(Document $document, bool $screen = false): array
    {
        $document->load('creator', 'reviewer', 'approver', 'contents', 'reviews');

        $schema = SchemaService::for($document->type);
        $logo = $this->pdfLogoDataUri();

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
            'schema' => $schema,
            'contentMap' => $document->contentMap(),
            'logo' => $logo,
            'stamp' => $this->pdfStampDataUri(),
            'embed' => $embed,
            'screen' => $screen,
            // Orientasi dibaca SEKALI dari schema; dipakai PDF (setPaper) maupun
            // preview (lebar kertas di layar) → tak mungkin beda.
            'orientation' => ($schema->raw()['orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait',
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
