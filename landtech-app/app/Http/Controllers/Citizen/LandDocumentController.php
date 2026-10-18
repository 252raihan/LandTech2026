<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\LandDocument;
use App\Models\LandRecord;
use App\Services\LandDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phase 5 — private document upload, listing and download.
 *
 * THE ONLY PATH TO FILE BYTES.
 *   There is no public URL for a land document. Every read goes through
 *   `download()` below, which:
 *     1. resolves the model through a route-model binding,
 *     2. checks LandDocumentPolicy — owner, or a reviewer who is not the owner,
 *     3. and only then streams the file.
 *
 *   A 403 is returned BEFORE any filesystem access, so an unauthorized user
 *   cannot use response timing or error content to learn whether a document
 *   exists. `findOrFail` on a nonexistent ID gives 404; a real-but-forbidden
 *   document gives 403 — neither discloses content.
 *
 * The upload path delegates to LandDocumentService, which owns all the
 * filename/MIME/disk decisions.
 */
class LandDocumentController extends Controller
{
    public function __construct(protected LandDocumentService $documents)
    {
    }

    /**
     * The citizen's own documents.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LandDocument::class);

        $documents = LandDocument::query()
            ->where('user_id', $request->user()->id)
            ->with('landRecord')
            ->latest()
            ->paginate(15);

        return view('citizen.seller.documents.index', [
            'documents' => $documents,
        ]);
    }

    /**
     * Upload form. Optionally scoped to a land record.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', LandDocument::class);

        $landRecord = null;

        if ($request->filled('land_record_id')) {
            // Owner-scoped: cannot attach to someone else's record.
            $landRecord = LandRecord::query()
                ->where('user_id', $request->user()->id)
                ->findOrFail($request->integer('land_record_id'));
        }

        return view('citizen.seller.documents.create', [
            'landRecord' => $landRecord,
            'records' => LandRecord::query()
                ->where('user_id', $request->user()->id)
                ->orderByDesc('id')
                ->get(),
            'documentTypes' => $this->documentTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LandDocument::class);

        $validated = $request->validate([
            'document_type' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys($this->documentTypes()))],
            'land_record_id' => ['nullable', 'integer'],
            // Extension + size here; the MIME/extension correspondence is
            // enforced again inside the service, which is the authority.
            'document' => [
                'required',
                'file',
                'max:'.LandDocument::MAX_SIZE_KB,
                'mimes:pdf,jpg,jpeg,png',
            ],
        ]);

        $landRecordId = null;

        if (! empty($validated['land_record_id'])) {
            $landRecordId = LandRecord::query()
                ->where('user_id', $request->user()->id)
                ->findOrFail($validated['land_record_id'])
                ->id;

            Gate::authorize('attachDocument', LandRecord::query()->findOrFail($landRecordId));
        }

        $document = $this->documents->store(
            $request->user(),
            $request->file('document'),
            [
                'document_type' => $validated['document_type'],
                'land_record_id' => $landRecordId,
            ]
        );

        return redirect()
            ->route('citizen.documents.show', $document)
            ->with('status', 'document-uploaded');
    }

    public function show(Request $request, LandDocument $document): View
    {
        $this->authorize('view', $document);

        return view('citizen.seller.documents.show', [
            'document' => $document->load(['landRecord', 'reviewer', 'replacement']),
        ]);
    }

    /**
     * Stream a private document. The ONLY way bytes leave the server.
     */
    public function download(Request $request, LandDocument $document): StreamedResponse
    {
        // Authorize BEFORE touching the filesystem.
        $this->authorize('download', $document);

        $absolute = $this->documents->resolvePath($document);

        // A sanitized, non-sensitive filename for the browser. Deliberately
        // generic: neither the stored path nor the client's original name is used.
        $extension = pathinfo($absolute, PATHINFO_EXTENSION);
        $downloadName = 'document-'.$document->id.($extension !== '' ? '.'.$extension : '');

        return response()->streamDownload(function () use ($absolute) {
            $handle = fopen($absolute, 'rb');

            if ($handle === false) {
                return;
            }

            fpassthru($handle);
            fclose($handle);
        }, $downloadName, [
            'Content-Type' => $document->mime_type,
            // Private + no-store: the file must not land in a shared cache.
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            // Never render an uploaded file inline in the origin.
            'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
        ]);
    }

    public function destroy(Request $request, LandDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        // Soft delete: the row is retained for the audit trail. The file itself
        // is left on disk so a reviewer's earlier decision stays verifiable.
        $document->delete();

        return redirect()
            ->route('citizen.documents.index')
            ->with('status', 'document-deleted');
    }

    /**
     * Approved document types, keyed by the stored value.
     *
     * @return array<string, string>
     */
    protected function documentTypes(): array
    {
        return [
            LandDocument::TYPE_DEED => 'citizen.documents.types.'.LandDocument::TYPE_DEED,
            LandDocument::TYPE_KHATIAN => 'citizen.documents.types.'.LandDocument::TYPE_KHATIAN,
            LandDocument::TYPE_MUTATION => 'citizen.documents.types.'.LandDocument::TYPE_MUTATION,
            LandDocument::TYPE_TAX_RECEIPT => 'citizen.documents.types.'.LandDocument::TYPE_TAX_RECEIPT,
            LandDocument::TYPE_PREVIOUS_DEED => 'citizen.documents.types.'.LandDocument::TYPE_PREVIOUS_DEED,
            LandDocument::TYPE_INHERITANCE => 'citizen.documents.types.'.LandDocument::TYPE_INHERITANCE,
            LandDocument::TYPE_MOUZA_MAP => 'citizen.documents.types.'.LandDocument::TYPE_MOUZA_MAP,
            LandDocument::TYPE_COURT_ORDER => 'citizen.documents.types.'.LandDocument::TYPE_COURT_ORDER,
            LandDocument::TYPE_OTHER => 'citizen.documents.types.'.LandDocument::TYPE_OTHER,
        ];
    }
}
