<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 5 — a private land document.
 *
 * SECURITY CONTRACT
 *   1. `stored_path` is a server-generated random name on the PRIVATE `local`
 *      disk. It is never derived from the uploaded filename.
 *   2. `original_name` is inert display metadata only. It must never be used to
 *      build a path, a URL or a log entry.
 *   3. There is no public URL for a document. Retrieval goes exclusively through
 *      LandDocumentController, which authorizes first.
 *   4. An upload is UNVERIFIED EVIDENCE. `review_status` starts `pending`.
 */
class LandDocument extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REPLACEMENT_REQUESTED = 'replacement_requested';

    public const TYPE_DEED = 'registered_deed';
    public const TYPE_KHATIAN = 'khatian';
    public const TYPE_MUTATION = 'mutation_record';
    public const TYPE_TAX_RECEIPT = 'land_tax_receipt';
    public const TYPE_PREVIOUS_DEED = 'previous_deed';
    public const TYPE_INHERITANCE = 'inheritance_certificate';
    public const TYPE_MOUZA_MAP = 'mouza_map';
    public const TYPE_COURT_ORDER = 'court_order';
    public const TYPE_OTHER = 'other';

    /**
     * Approved upload types. Extensions AND MIME types are both enforced.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];

    public const MAX_SIZE_KB = 10240; // 10 MB

    protected $fillable = [
        'user_id',
        'land_record_id',
        'document_type',
        'original_name',
        'stored_path',
        'disk',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'replaced_by_document_id',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function landRecord(): BelongsTo
    {
        return $this->belongsTo(LandRecord::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_document_id');
    }

    /**
     * Safe, human-readable size for display.
     */
    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Human-readable type key for the UI. Translated in the view layer.
     */
    public function typeLabelKey(): string
    {
        return 'citizen.documents.types.' . $this->document_type;
    }

    public function isPending(): bool
    {
        return $this->review_status === self::STATUS_PENDING;
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
