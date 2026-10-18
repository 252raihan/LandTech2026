<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 5 — a citizen's submitted land record.
 *
 * HONESTY CONTRACT
 *   - A submission is a CLAIM, never a determination. New records enter at
 *     `pending_verification` and only a reviewer may change that.
 *   - `claim_share_*` is what the user asserts, stored as an exact fraction.
 *     It is never assumed to be the whole plot.
 *   - `plot_id` / `party_id` are nullable: a claim can describe land with no
 *     registry row, and can exist before any verified party link.
 *
 * ACCESS
 *   Every citizen-facing query must be scoped by `user_id`. See LandRecordPolicy.
 */
class LandRecord extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_ADDITIONAL_INFO_REQUIRED = 'additional_information_requested';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CORRECTION_REQUESTED = 'correction_requested';

    public const INTENT_RECORD_KEEPING = 'record_keeping';
    public const INTENT_SALE_LISTING = 'sale_listing';

    public const SURVEY_TYPES = ['CS', 'SA', 'RS', 'BS'];

    public const AREA_UNITS = ['decimal', 'katha', 'bigha', 'acre', 'sqft', 'sqm'];

    public const BASIS_PURCHASE = 'purchase';
    public const BASIS_INHERITANCE = 'inheritance';
    public const BASIS_GIFT_HEBA = 'gift_heba';
    public const BASIS_COURT_DECREE = 'court_decree';
    public const BASIS_SETTLEMENT = 'settlement';
    public const BASIS_OTHER = 'other';

    public const MUTATION_NOT_DONE = 'not_done';
    public const MUTATION_IN_PROGRESS = 'in_progress';
    public const MUTATION_DONE = 'done';
    public const MUTATION_UNKNOWN = 'unknown';

    protected $fillable = [
        'user_id',
        'plot_id',
        'party_id',
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
        'claim_share_numerator',
        'claim_share_denominator',
        'ownership_basis',
        'ownership_basis_other',
        'deed_no',
        'deed_date',
        'registration_office',
        'mutation_status',
        'mutation_no',
        'intended_use',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:4',
            'claim_share_numerator' => 'integer',
            'claim_share_denominator' => 'integer',
            'deed_date' => 'date',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function mouza(): BelongsTo
    {
        return $this->belongsTo(Mouza::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LandDocument::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(SaleListing::class);
    }

    /**
     * Exact claimed share in lowest terms, e.g. "1/3". Display only.
     */
    public function shareLabel(): string
    {
        return $this->claim_share_numerator . '/' . $this->claim_share_denominator;
    }

    /**
     * The claimed share as a ratio. NEVER use for arithmetic — use the fraction.
     */
    public function shareRatio(): float
    {
        if ((int) $this->claim_share_denominator === 0) {
            return 0.0;
        }

        return $this->claim_share_numerator / $this->claim_share_denominator;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * A record is editable by its owner only while it has not been locked by the
     * review process.
     */
    public function isEditableByOwner(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_CORRECTION_REQUESTED,
            self::STATUS_ADDITIONAL_INFO_REQUIRED,
            self::STATUS_REJECTED,
        ], true);
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }
}
