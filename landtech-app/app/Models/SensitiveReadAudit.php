<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 3 — append-only audit row for sensitive identity access.
 *
 * There is deliberately NO `updated_at`: rows are immutable. Creation goes
 * through App\Support\Audit\AuditChain, which is the only place that should
 * assign chain_position / prev_hash / record_hash.
 *
 * @property int $chain_position
 * @property string $prev_hash
 * @property string $record_hash
 */
class SensitiveReadAudit extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_VIEW_MASKED = 'view_masked';
    public const ACTION_VIEW_FULL = 'view_full';
    public const ACTION_SEARCH_NID = 'search_nid';
    public const ACTION_EXPORT = 'export';

    protected $fillable = [
        'user_id',
        'action',
        'purpose',
        'subject_party_id',
        'subject_identity_id',
        'ip_address',
        'user_agent',
        'chain_position',
        'prev_hash',
        'record_hash',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'chain_position' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjectParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'subject_party_id');
    }

    public function subjectIdentity(): BelongsTo
    {
        return $this->belongsTo(PartyIdentity::class, 'subject_identity_id');
    }
}
