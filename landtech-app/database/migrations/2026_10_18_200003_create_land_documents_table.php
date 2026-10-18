<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Private land documents.
 *
 * STORAGE SECURITY
 * ----------------
 *  - Files are written to the `local` disk, whose root is storage/app/private —
 *    OUTSIDE the public web root. There is no public URL for any of them.
 *  - `stored_path` holds a server-generated random name. The user-supplied
 *    filename is kept ONLY in `original_name` as inert display metadata and is
 *    never used to build a filesystem path.
 *  - Retrieval happens exclusively through an authenticated, policy-checked
 *    controller action (LandDocumentController::download), never a static URL.
 *  - `checksum_sha256` lets a reviewer detect a swapped file after upload.
 *
 * WHAT A DOCUMENT IS NOT
 * ----------------------
 * An uploaded file is UNVERIFIED EVIDENCE. `review_status` starts `pending` and
 * only an authorized reviewer may move it. Nothing here asserts a document is
 * genuine, and no full NID is ever stored in a filename, path or metadata.
 */
return new class extends Migration
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REPLACEMENT_REQUESTED = 'replacement_requested';

    public function up(): void
    {
        Schema::create('land_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // A document may be attached to a land record, a verification
            // request, or (transiently) neither while a draft is assembled.
            $table->foreignId('land_record_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('document_type', 40);

            // Inert display metadata ONLY — never used as a path component.
            $table->string('original_name', 191)->nullable();

            // Server-generated random path on the private disk.
            $table->string('stored_path', 255);
            $table->string('disk', 32)->default('local');

            $table->string('mime_type', 128);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum_sha256', 64);

            $table->string('review_status', 24)->default(self::STATUS_PENDING);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            // Replacement chain: a superseded document points at its successor.
            $table->foreignId('replaced_by_document_id')->nullable()
                ->constrained('land_documents')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'review_status']);
            $table->index('land_record_id');
            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_documents');
    }
};
