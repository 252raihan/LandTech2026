<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 5 — a sale listing.
 *
 * TWO INDEPENDENT AXES (the core safety property):
 *   `status`              — owner/review lifecycle. Owner-controllable.
 *   `verification_status` — reviewer-only. An owner can NEVER set this.
 *
 * Public search reads ONLY `status = published`. Unverified listings are
 * published with an explicit label, never silently presented as verified.
 *
 * This is an ADVERTISEMENT, not a transfer of title. No payment, deed
 * registration or mutation occurs in this workflow.
 */
class SaleListing extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_SOLD = 'sold';

    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_DOCUMENT_REVIEWED = 'document_reviewed';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_REJECTED = 'rejected';

    public const SALE_FULL_PLOT = 'full_plot';
    public const SALE_PARTIAL = 'partial';

    public const PRICE_UNITS = ['BDT', 'lakh', 'crore'];
    public const CONTACT_VIA_PLATFORM = 'via_platform';
    public const CONTACT_PHONE = 'phone';
    public const CONTACT_EMAIL = 'email';

    protected $fillable = [
        'reference_no',
        'user_id',
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
        'area_unit',
        'asking_price',
        'price_unit',
        'sale_type',
        'claim_share_numerator',
        'claim_share_denominator',
        'title',
        'public_description',
        'contact_preference',
        'status',
        'verification_status',
        'submitted_at',
        'published_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:4',
            'asking_price' => 'decimal:2',
            'claim_share_numerator' => 'integer',
            'claim_share_denominator' => 'integer',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
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

    public function interestRequests(): HasMany
    {
        return $this->hasMany(BuyerInterestRequest::class);
    }

    public function shareLabel(): string
    {
        return $this->claim_share_numerator . '/' . $this->claim_share_denominator;
    }

    public function shareRatio(): float
    {
        if ((int) $this->claim_share_denominator === 0) {
            return 0.0;
        }

        return $this->claim_share_numerator / $this->claim_share_denominator;
    }

    /**
     * Is this listing publicly discoverable?
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Has the underlying land claim been verified by a reviewer?
     * Drives the mandatory "unverified" labelling in the UI.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    /**
     * The owner may edit only while the listing has not entered the review or
     * published pipeline.
     */
    public function isEditableByOwner(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_WITHDRAWN,
        ], true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
