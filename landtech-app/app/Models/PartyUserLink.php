<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 5 — a verified (or pending) link between a User account and a Party.
 *
 * AUTHORITY RULE
 *   `status === 'verified'` is the ONLY thing that lets a user act on behalf of
 *   a party. A `pending` link confers nothing. This is what stops a citizen
 *   claiming land by simply submitting an identity number: the link must be
 *   reviewed and approved by someone other than the claimant.
 */
class PartyUserLink extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    public const BASIS_NID = 'nid';
    public const BASIS_BIRTH_REGISTRATION = 'birth_registration';
    public const BASIS_INHERITANCE = 'inheritance';
    public const BASIS_COURT_ORDER = 'court_order';
    public const BASIS_OTHER = 'other';

    protected $fillable = [
        'user_id',
        'party_id',
        'status',
        'claim_basis',
        'claim_note',
        'evidence_document_id',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function evidenceDocument(): BelongsTo
    {
        return $this->belongsTo(LandDocument::class, 'evidence_document_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }
}
