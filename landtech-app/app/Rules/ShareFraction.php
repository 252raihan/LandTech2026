<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an ownership share expressed as an exact fraction.
 *
 * Enforces:
 *   - numerator and denominator are positive integers
 *   - denominator is non-zero (division safety for shareRatio())
 *   - the fraction does not exceed 1 (no one owns more than the whole plot)
 *
 * Fractions are used instead of percentages so 1/3 stays exact and future
 * Faraz/inheritance arithmetic does not accumulate float error.
 *
 * The rule is constructed with the SIBLING field's value, so the validator
 * checks the pair together.
 */
class ShareFraction implements ValidationRule
{
    public function __construct(
        protected mixed $numerator,
        protected mixed $denominator,
        protected string $numeratorAttribute = 'share numerator',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $num = $this->numerator;
        $den = $this->denominator;

        if (! is_numeric($num) || ! is_numeric($den)) {
            $fail('The :attribute must be a numeric fraction.')->translate();

            return;
        }

        $num = (int) $num;
        $den = (int) $den;

        if ($num < 1) {
            $fail("The {$this->numeratorAttribute} must be a positive integer.")->translate();

            return;
        }

        if ($den < 1) {
            $fail('The share denominator must be a positive integer (division by zero is not allowed).')->translate();

            return;
        }

        if ($num > $den) {
            $fail('The ownership share cannot exceed 1 (the whole plot).')->translate();
        }
    }
}
