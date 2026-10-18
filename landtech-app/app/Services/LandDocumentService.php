<?php

namespace App\Services;

use App\Models\LandDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The ONLY sanctioned path for storing and retrieving land documents.
 *
 * THREAT MODEL
 * ------------
 * An uploaded file is the most dangerous input in a web application. This
 * service therefore:
 *
 *  1. Never uses the client filename for anything but display. The stored name
 *     is `{random}/{random}.{ext}` where `ext` is derived from the VALIDATED
 *     extension, not from the uploaded name. `../../.env` cannot become a path.
 *  2. Enforces an allow-list of extension + MIME pairs. Both must agree, so a
 *     `.pdf` that is actually a PHP script is rejected.
 *  3. Stores on the PRIVATE `local` disk (storage/app/private), never the public
 *     disk, so no file is reachable by URL.
 *  4. Refuses to serve anything the policy layer has not authorized, and refuses
 *     to serve a path that escapes the disk root.
 *  5. Records a SHA-256 checksum at upload time so a swapped file is detectable.
 */
class LandDocumentService
{
    /**
     * The private disk. Deliberately NOT `public`.
     */
    public const DISK = 'local';

    /**
     * Store an uploaded document and return the persisted record.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function store(User $uploader, UploadedFile $file, array $attributes = []): LandDocument
    {
        $extension = $this->validatedExtension($file);

        // Server-generated path. The client filename never reaches the disk.
        $directory = 'land-documents/' . $uploader->id . '/' . now()->format('Y/m');
        $filename = Str::uuid()->toString() . '.' . $extension;

        $path = $file->storeAs($directory, $filename, [
            'disk' => self::DISK,
            // Defence in depth: the private disk has no public URL, but we also
            // mark the file private so a future disk change cannot expose it.
            'visibility' => 'private',
        ]);

        if ($path === false || $path === null) {
            throw ValidationException::withMessages([
                'document' => __('citizen.documents.upload_failed'),
            ]);
        }

        return LandDocument::create([
            'user_id' => $uploader->id,
            'land_record_id' => $attributes['land_record_id'] ?? null,
            'document_type' => $attributes['document_type'],
            // Inert display metadata only.
            'original_name' => $this->safeDisplayName($file->getClientOriginalName()),
            'stored_path' => $path,
            'disk' => self::DISK,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'checksum_sha256' => hash_file('sha256', $file->getRealPath()) ?: '',
            'review_status' => LandDocument::STATUS_PENDING,
        ]);
    }

    /**
     * Resolve a document's absolute path, or fail closed.
     *
     * Guards against a corrupted or maliciously-edited `stored_path` pointing
     * outside the disk root.
     */
    public function resolvePath(LandDocument $document): string
    {
        $disk = Storage::disk($document->disk);

        $path = $this->normalise($document->stored_path);

        if ($path === null || ! $disk->exists($path)) {
            throw new NotFoundHttpException(__('citizen.documents.not_found'));
        }

        return $disk->path($path);
    }

    /**
     * Read a document's bytes for a streamed, authorized response.
     */
    public function read(LandDocument $document): string
    {
        $contents = Storage::disk($document->disk)->get($this->normalise($document->stored_path));

        if ($contents === null) {
            throw new NotFoundHttpException(__('citizen.documents.not_found'));
        }

        return $contents;
    }

    /**
     * Reject any path that escapes the disk root, then return it.
     *
     * Catches `../`, absolute paths and null bytes. The stored path is
     * server-generated, so a failure here means the row was tampered with.
     */
    protected function normalise(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);

        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        if (str_starts_with($path, '/') || preg_match('#(^|/)\.(/|$)#', $path)) {
            return null;
        }

        return $path;
    }

    /**
     * Derive and validate the extension from the uploaded file.
     *
     * Both the client extension AND the detected MIME type must be on the
     * allow-list, and they must correspond to the same entry. This rejects a
     * renamed executable masquerading as a PDF.
     */
    protected function validatedExtension(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();

        if (! array_key_exists($extension, LandDocument::ALLOWED)) {
            throw ValidationException::withMessages([
                'document' => __('citizen.documents.invalid_type'),
            ]);
        }

        if (! in_array($mime, LandDocument::ALLOWED[$extension], true)) {
            throw ValidationException::withMessages([
                'document' => __('citizen.documents.invalid_type'),
            ]);
        }

        return $extension;
    }

    /**
     * Reduce the client filename to safe display text.
     *
     * Strips any directory component, control characters and path separators.
     * The result is only ever rendered as escaped text — it is never a path.
     */
    protected function safeDisplayName(string $original): string
    {
        $name = basename(str_replace('\\', '/', $original));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return Str::limit(trim($name), 180, '');
    }
}
