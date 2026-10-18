<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 5 — a buyer-initiated ownership verification request.
 *
 * May reference land that has NO listing, which is why the land identifiers are
 * stored on this row directly.
 *
 * SEPARATION OF DUTIES
 *   The requester (`user_id`) can never be the reviewer (`reviewed_by`). That
 *   rule is enforced in VerificationRequestPolicy and asserted by tests.
 *
 * WHAT AN OUTCOME MEANS
 *   `official_status` distinguishes document review and internal consistency
 *   from official verification. There is NO government integration in this
 *   project, so `officially_verified` is deliberately not a reachable value and
 *   the UI must never render "Government Verified".
 */
class VerificationRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_ADDITIONAL_INFO_REQUIRED = 'additional_information_requested';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_CLOSED = 'closed';

    public const OFFICIAL_NOT_AVAILABLE = 'official_verification_not_available';
    public const OFFICIAL_PENDING = 'official_verification_pending';

    protected $fillable = [
        'reference_no',
        'user_id',
        'sale_listing_id',
        'land_record_id',
        'plot_id',
        'mouza_id',
        'district_name',
        'upazila_name',
        'mouza_name',
        'jl_no',
        'survey_type',
        'khatian_no',
        'dag_no',
        'area',
        'deed_no',
        'registration_office',
        'reason',
        'evidence_document_id',
        'status',
        'official_status',
        'risk_indicator',
        'outcome_summary',
        'outcome_limitations',
        'outcome_document_id',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:4',
            'risk_indicator' => 'integer',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(SaleListing::class, 'sale_listing_id');
    }

    public function landRecord(): BelongsTo
    {
        return $this->belongsTo(LandRecord::class);
    }

    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }

    public function mouza(): BelongsTo
    {
        return $this->belongsTo(Mouza::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function evidenceDocument(): BelongsTo
    {
        return $this->belongsTo(LandDocument::class, 'evidence_document_id');
    }

    public function outcomeDocument(): BelongsTo
    {
        return $this->belongsTo(LandDocument::class, 'outcome_document_id');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(VerificationCheck::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_ADDITIONAL_INFO_REQUIRED,
        ], true);
    }

    /**
     * Whether official verification is available at all. Always false in this
     * build — there is no government integration. Kept as a method so the UI
     * asks a question rather than hardcoding an assumption in a template.
     */
    public function officialVerificationAvailable(): bool
    {
        return $this->official_status === 'officially_verified';
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
