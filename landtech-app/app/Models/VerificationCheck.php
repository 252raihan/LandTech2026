<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 5 — one individual check within a verification request.
 *
 * A verification outcome is deliberately NOT a single boolean. Each check
 * records its own result and its own explicit limitation, so the UI can state
 * precisely what was and was not established.
 */
class VerificationCheck extends Model
{
    use HasFactory;

    public const RESULT_PASS = 'pass';
    public const RESULT_FAIL = 'fail';
    public const RESULT_INCONCLUSIVE = 'inconclusive';
    public const RESULT_NOT_AVAILABLE = 'not_available';
    public const RESULT_PENDING = 'pending';

    /**
     * The checks the system can record, with whether each is automatable from
     * our own data or requires an authorized external source we do not have.
     *
     * @var array<string, bool>
     */
    public const CATALOGUE = [
        'record_identity_consistency' => true,
        'deed_record_consistency' => true,
        'ownership_share_consistency' => true,
        'ownership_chain_evidence' => true,
        'duplicate_sale_indicators' => true,
        'mutation_status' => false,
        'lien_mortgage_status' => false,
        'court_dispute_status' => false,
    ];

    protected $fillable = [
        'verification_request_id',
        'check_code',
        'result',
        'detail',
        'limitation',
        'is_automated',
        'performed_by',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_automated' => 'boolean',
            'performed_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(VerificationRequest::class, 'verification_request_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * True when this check requires an authorized external source that this
     * build does not integrate with. Such checks are recorded as
     * `not_available` rather than silently skipped or faked.
     */
    public function requiresExternalSource(): bool
    {
        return self::CATALOGUE[$this->check_code] === false;
    }

    public function labelKey(): string
    {
        return 'citizen.verification.checks.' . $this->check_code;
    }
}
