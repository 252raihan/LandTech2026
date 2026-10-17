<?php

namespace App\Rules;

use App\Support\Identity\IdentityNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an identity number, accepting BOTH Bengali and ASCII digits.
 *
 * Phase 3 uses synthetic data only, so the default accepted shape is the
 * existing `SIM-` convention (SIM-123456 / SIM-ORG-0001). Real-world NID rules
 * (10, 13 or 17 digits) are supported via $allowRealFormat for future phases
 * but are OFF by default to keep the demo dataset unambiguously synthetic.
 */
class IdentityNumber implements ValidationRule
{
    public function __construct(
        protected bool $allowRealFormat = false,
        protected int $minLength = 6,
        protected int $maxLength = 32,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('The :attribute field is required.')->translate();

            return;
        }

        // Bengali digits are folded to ASCII here so the check is script-agnostic.
        $normalized = IdentityNormalizer::normalize($value);

        if ($normalized === '') {
            $fail('The :attribute must contain digits.')->translate();

            return;
        }

        if (! $this->allowRealFormat && ! str_starts_with($normalized, 'SIM')) {
            $fail('The :attribute must be a synthetic identifier beginning with SIM- in this phase.')->translate();

            return;
        }

        if (strlen($normalized) < $this->minLength || strlen($normalized) > $this->maxLength) {
            $fail("The :attribute must be between {$this->minLength} and {$this->maxLength} characters.")->translate();

            return;
        }

        if (! preg_match('/^[A-Z0-9]+$/', $normalized)) {
            $fail('The :attribute may only contain letters, digits and separators.')->translate();
        }
    }
}
