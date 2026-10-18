<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 5 — a buyer's enquiry against a published listing.
 *
 * NOT a contract, reservation, payment or transfer of title. No deed
 * registration or mutation happens in this workflow, and the UI must not imply
 * that expressing interest has any legal effect.
 *
 * PRIVACY: buyer contact details are not stored here. The owner decides whether
 * to share contact information (see `contact_shared`).
 */
class BuyerInterestRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_CONTACT_SHARED = 'contact_shared';
    public const STATUS_SITE_VISIT_PROPOSED = 'site_visit_proposed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'reference_no',
        'sale_listing_id',
        'user_id',
        'message',
        'status',
        'seller_response',
        'responded_at',
        'contact_shared',
    ];

    protected function casts(): array
    {
        return [
            'contact_shared' => 'boolean',
            'responded_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(SaleListing::class, 'sale_listing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
