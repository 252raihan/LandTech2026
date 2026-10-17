<?php

namespace App\Models;

use App\Support\Identity\IdentityNormalizer;
use App\Support\Identity\NidHasher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 3 — sensitive identity material for a Party (NID, birth registration, etc.).
 *
 * SECURITY CONTRACT
 * -----------------
 *  1. `id_number_encrypted` and `id_number_lookup_hmac` are in `$hidden`, so they
 *     never appear in array/JSON serialization.
 *  2. `id_number` is NOT a column and NOT fillable — the raw value is written
 *     through the guarded `setIdentityNumber()` method and read back through the
 *     audited `reveal()` method.
 *  3. Default reads are masked. `maskedNumber()` is safe to render anywhere.
 *  4. `reveal()` does not itself audit — callers go through the audit service so
 *     the actor and purpose are always captured. See App\Support\Audit\AuditChain.
 */
class PartyIdentity extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_NID = 'nid';
    public const TYPE_BIRTH_REGISTRATION = 'birth_registration';
    public const TYPE_PASSPORT = 'passport';
    public const TYPE_ORG_REG = 'org_reg';

    protected $fillable = [
        'party_id',
        'id_type',
        'id_last4',
        'key_version',
        'is_primary',
        'verified_at',
        'verified_by',
    ];

    /**
     * Never serialize identity material. This is the primary defence against
     * accidental leakage through JSON responses and API resources.
     */
    protected $hidden = [
        'id_number_encrypted',
        'id_number_lookup_hmac',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'key_version' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Store a raw identity number: encrypted payload + keyed lookup hash + mask fragment.
     *
     * Both the encryption and the HMAC require NID_HMAC_KEY; if it is absent the
     * NidHasher throws NidKeyMissing and nothing is persisted (fail-closed).
     */
    public function setIdentityNumber(string $raw): static
    {
        $hasher = app(NidHasher::class);

        $this->id_number_encrypted = $hasher->encrypt($raw);
        $this->id_number_lookup_hmac = $hasher->lookupHash($raw);
        $this->id_last4 = IdentityNormalizer::trailing($raw, (int) config('privacy.mask_visible_trailing', 4));
        $this->key_version = $hasher->currentKeyVersion();

        return $this;
    }

    /**
     * Decrypt the stored identity number.
     *
     * PRIVILEGED. Callers must be authorized AND must record an audit entry.
     * Prefer `maskedNumber()` unless the full value is genuinely required.
     */
    public function reveal(): string
    {
        return app(NidHasher::class)->decrypt($this->id_number_encrypted);
    }

    /**
     * Safe, human-readable masked form: •••• 1234.
     */
    public function maskedNumber(): string
    {
        $char = (string) config('privacy.mask_character', '•');

        if (! $this->id_last4) {
            return str_repeat($char, 4);
        }

        return str_repeat($char, 4) . ' ' . $this->id_last4;
    }

    /**
     * Build the deterministic lookup hash for a search term.
     *
     * Returns every accepted key generation so lookups keep working while a key
     * rotation is in flight (see config/privacy.php rotation runbook).
     *
     * @return array<int, string>
     */
    public static function lookupHashesFor(string $raw): array
    {
        return app(NidHasher::class)->candidateHashes($raw);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('id_type', $type);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
