<?php

namespace App\Support\Identity;

use RuntimeException;

/**
 * Thrown when an identity operation is attempted without a usable NID HMAC key.
 *
 * Raised only from NID code paths — never from application boot — so public
 * demo pages and Phase 1/2 flows are unaffected on a machine with no key set.
 */
class NidKeyMissing extends RuntimeException
{
    public static function make(): self
    {
        return new self(
            'NID_HMAC_KEY is not configured. Identity encryption, lookup and '
                . 'search are disabled (fail-closed). Set NID_HMAC_KEY in the local '
                . '.env — generate one with: php -r "echo base64_encode(random_bytes(32));"'
        );
    }
}
