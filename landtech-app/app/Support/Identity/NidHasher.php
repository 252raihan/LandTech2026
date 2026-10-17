<?php

namespace App\Support\Identity;

use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts identity numbers and derives their keyed lookup hash.
 *
 * Two distinct primitives, two distinct keys:
 *
 *   encrypt()  -> Laravel Crypt (AES-256-CBC, APP_KEY). Randomized output, so
 *                 the ciphertext is never searchable and never indexed.
 *
 *   lookupHash() -> hash_hmac('sha256', normalized, NID_HMAC_KEY). Deterministic
 *                 and indexable, but useless without the key.
 *
 * WHY NOT PLAIN SHA-256: a Bangladeshi NID is 10-17 digits, so an unkeyed
 * sha256 digest can be brute-forced exhaustively in seconds. A keyed HMAC
 * removes that attack entirely while keeping deterministic lookups.
 */
class NidHasher
{
    /**
     * Encrypt a raw identity number for storage.
     */
    public function encrypt(string $raw): string
    {
        $this->guardKey();

        return Crypt::encryptString(IdentityNormalizer::normalize($raw));
    }

    /**
     * Decrypt a stored identity number back to its normalized form.
     */
    public function decrypt(string $ciphertext): string
    {
        $this->guardKey();

        return Crypt::decryptString($ciphertext);
    }

    /**
     * Derive the deterministic, keyed lookup hash for a raw identity number.
     *
     * @param  int|null  $keyVersion  Override the key generation (used by the
     *                                rotation re-hash pass). Null = current.
     */
    public function lookupHash(string $raw, ?int $keyVersion = null): string
    {
        return hash_hmac('sha256', IdentityNormalizer::normalize($raw), $this->resolveKey($keyVersion));
    }

    /**
     * Look up a raw identity number against a set of candidate hashes.
     *
     * During a key rotation the same NID hashes differently per key version, so
     * the query must consider every accepted version until the re-hash pass has
     * stamped all rows with the current one.
     *
     * @return array<int, string> Hashes ordered by key version.
     */
    public function candidateHashes(string $raw): array
    {
        $versions = (array) config('privacy.accepted_key_versions', [config('privacy.nid_hmac_key_version', 1)]);
        $hashes = [];

        foreach ($versions as $version) {
            $hashes[(int) $version] = $this->lookupHash($raw, (int) $version);
        }

        return $hashes;
    }

    /**
     * The key generation new writes should be stamped with.
     */
    public function currentKeyVersion(): int
    {
        return (int) config('privacy.nid_hmac_key_version', 1);
    }

    /**
     * Resolve the HMAC key for a given generation.
     *
     * Only the current generation has a key in config; a rotation pass supplies
     * the outgoing key through `privacy.nid_hmac_previous_key` for the duration
     * of the migration window.
     */
    protected function resolveKey(?int $keyVersion): string
    {
        $current = $this->currentKeyVersion();

        if ($keyVersion === null || $keyVersion === $current) {
            $key = config('privacy.nid_hmac_key');

            if (! is_string($key) || $key === '') {
                throw NidKeyMissing::make();
            }

            return $this->decodeKey($key);
        }

        $previous = config('privacy.nid_hmac_previous_key');

        if (! is_string($previous) || $previous === '') {
            throw NidKeyMissing::make();
        }

        return $this->decodeKey($previous);
    }

    /**
     * Accept both base64-encoded 256-bit keys and raw 32-byte strings.
     */
    protected function decodeKey(string $key): string
    {
        $decoded = base64_decode($key, true);

        if ($decoded !== false && strlen($decoded) === 32) {
            return $decoded;
        }

        return $key;
    }

    protected function guardKey(): void
    {
        if (! config('privacy.fail_closed', true)) {
            return;
        }

        $key = config('privacy.nid_hmac_key');

        if (! is_string($key) || $key === '') {
            throw NidKeyMissing::make();
        }
    }
}
