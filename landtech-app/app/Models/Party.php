<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 3 — a Party is a person or an organization that can hold land interests.
 *
 * `parties` intentionally holds NO identity numbers. NID material lives in
 * `party_identities` so it can be authorized, masked and audited separately.
 */
class Party extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_ORGANIZATION = 'organization';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_MERGED = 'merged';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'party_type',
        'display_name_en',
        'display_name_bn',
        'legal_name_en',
        'legal_name_bn',
        'org_registration_no',
        'date_of_birth',
        'gender',
        'status',
        'merged_into_party_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function identities(): HasMany
    {
        return $this->hasMany(PartyIdentity::class);
    }

    /**
     * The primary identity record, if one has been registered.
     */
    public function primaryIdentity(): ?PartyIdentity
    {
        return $this->identities()->where('is_primary', true)->first();
    }

    public function ownerships(): HasMany
    {
        return $this->hasMany(PlotOwnership::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_party_id');
    }

    public function isOrganization(): bool
    {
        return $this->party_type === self::TYPE_ORGANIZATION;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
