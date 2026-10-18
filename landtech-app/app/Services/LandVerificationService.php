<?php

namespace App\Services;

use App\Models\LandRecord;
use App\Models\Plot;
use App\Models\SaleListing;
use App\Models\VerificationCheck;
use App\Models\VerificationRequest;
use Illuminate\Support\Collection;

/**
 * Runs the checks the system can HONESTLY perform, and records the ones it
 * cannot as explicitly unavailable.
 *
 * WHAT THIS IS
 * ------------
 * Internal consistency checking against records the platform already holds:
 * identifier agreement, share arithmetic, ownership-chain presence, and
 * duplicate-sale indicators. All of it is deterministic string/number/row
 * comparison. No external system is contacted.
 *
 * WHAT THIS IS NOT
 * ----------------
 * There is NO government integration in this project. We cannot query the
 * Directorate of Registration, the Land Record Web Portal, the NBR, or any court
 * database. Checks that would require those sources (mutation status, lien or
 * mortgage position, court disputes) are recorded as `not_available` with an
 * explicit limitation, NEVER guessed at and never inferred from a user's own
 * assertion.
 *
 * A `risk_indicator` produced here is a WARNING INDICATOR. It is not a finding
 * of fraud and must never be presented as proof.
 */
class LandVerificationService
{
    /**
     * Run every applicable check against a request and persist the results.
     *
     * @return Collection<int, VerificationCheck>
     */
    public function runChecks(VerificationRequest $request): Collection
    {
        $checks = [];

        foreach (array_keys(VerificationCheck::CATALOGUE) as $code) {
            $checks[] = $this->runCheck($request, $code);
        }

        return collect($checks);
    }

    /**
     * Execute one check by code.
     */
    public function runCheck(VerificationRequest $request, string $code): VerificationCheck
    {
        $result = match ($code) {
            'record_identity_consistency' => $this->recordIdentityConsistency($request),
            'deed_record_consistency' => $this->deedRecordConsistency($request),
            'ownership_share_consistency' => $this->ownershipShareConsistency($request),
            'ownership_chain_evidence' => $this->ownershipChainEvidence($request),
            'duplicate_sale_indicators' => $this->duplicateSaleIndicators($request),
            // These three require an authorized external source we do not have.
            'mutation_status',
            'lien_mortgage_status',
            'court_dispute_status' => $this->unavailableCheck($code),
            default => $this->unknownCheck($code),
        };

        return VerificationCheck::updateOrCreate(
            [
                'verification_request_id' => $request->id,
                'check_code' => $code,
            ],
            $result
        );
    }

    /**
     * Do our own records agree with the identifiers the requester supplied?
     *
     * Compares against `plots` and `land_records` we already hold. A mismatch is
     * not fraud — it may simply mean the land is not in our dataset.
     */
    protected function recordIdentityConsistency(VerificationRequest $request): array
    {
        if (! $request->khatian_no || ! $request->dag_no) {
            return $this->pending('record_identity_consistency');
        }

        $plotMatches = Plot::query()
            ->where('khatian_no', $request->khatian_no)
            ->where('plot_no', $request->dag_no)
            ->when($request->mouza_id, fn($q) => $q->where('mouza_id', $request->mouza_id))
            ->get();

        if ($plotMatches->isEmpty()) {
            return [
                'result' => VerificationCheck::RESULT_INCONCLUSIVE,
                'detail' => __('citizen.verification.detail.no_plot_match'),
                'limitation' => __('citizen.verification.limitation.internal_records_only'),
                'is_automated' => true,
                'performed_at' => now(),
            ];
        }

        // If a mouza was supplied, check the plot's mouza agrees with it.
        $consistent = $request->mouza_id === null
            || $plotMatches->contains(fn(Plot $p) => $p->mouza_id === $request->mouza_id);

        return [
            'result' => $consistent
                ? VerificationCheck::RESULT_PASS
                : VerificationCheck::RESULT_FAIL,
            'detail' => $consistent
                ? __('citizen.verification.detail.plot_identifiers_agree')
                : __('citizen.verification.detail.plot_mouza_conflict'),
            'limitation' => __('citizen.verification.limitation.internal_records_only'),
            'is_automated' => true,
            'performed_at' => now(),
        ];
    }

    /**
     * Is the supplied deed reference internally well-formed and present?
     *
     * We can only check shape and cross-reference our own data. We CANNOT check
     * the deed against any registration authority.
     */
    protected function deedRecordConsistency(VerificationRequest $request): array
    {
        if (! $request->deed_no) {
            return [
                'result' => VerificationCheck::RESULT_NOT_AVAILABLE,
                'detail' => __('citizen.verification.detail.no_deed_supplied'),
                'limitation' => __('citizen.verification.limitation.no_registry_access'),
                'is_automated' => true,
                'performed_at' => now(),
            ];
        }

        $referencing = LandRecord::query()
            ->where('deed_no', $request->deed_no)
            ->when($request->registration_office, fn($q) => $q->where('registration_office', $request->registration_office))
            ->count();

        return [
            'result' => $referencing > 0
                ? VerificationCheck::RESULT_PASS
                : VerificationCheck::RESULT_INCONCLUSIVE,
            'detail' => $referencing > 0
                ? __('citizen.verification.detail.deed_referenced_by_records', ['count' => $referencing])
                : __('citizen.verification.detail.deed_not_in_records'),
            'limitation' => __('citizen.verification.limitation.no_registry_access'),
            'is_automated' => true,
            'performed_at' => now(),
        ];
    }

    /**
     * Do recorded ownership shares for the plot add up sensibly?
     *
     * Uses the exact Phase 3 fractions. This is arithmetic over our own data —
     * it says nothing about whether those records are themselves accurate.
     */
    protected function ownershipShareConsistency(VerificationRequest $request): array
    {
        if (! $request->plot_id) {
            return [
                'result' => VerificationCheck::RESULT_NOT_AVAILABLE,
                'detail' => __('citizen.verification.detail.no_plot_linked'),
                'limitation' => __('citizen.verification.limitation.internal_records_only'),
                'is_automated' => true,
                'performed_at' => now(),
            ];
        }

        $plot = Plot::query()->find($request->plot_id);

        if (! $plot) {
            return $this->pending('ownership_share_consistency');
        }

        $ownerships = $plot->currentOwnerships()->get();

        if ($ownerships->isEmpty()) {
            return [
                'result' => VerificationCheck::RESULT_INCONCLUSIVE,
                'detail' => __('citizen.verification.detail.no_ownership_rows'),
                'limitation' => __('citizen.verification.limitation.internal_records_only'),
                'is_automated' => true,
                'performed_at' => now(),
            ];
        }

        // Exact-fraction sum via a common denominator.
        $denominator = $ownerships->reduce(
            fn(int $carry, $o) => $this->lcm($carry, (int) $o->share_denominator),
            1
        );

        $numerator = $ownerships->reduce(
            fn(int $carry, $o) => $carry + ((int) $o->share_numerator * intdiv($denominator, (int) $o->share_denominator)),
            0
        );

        $balanced = $numerator === $denominator;

        return [
            'result' => $balanced
                ? VerificationCheck::RESULT_PASS
                : VerificationCheck::RESULT_INCONCLUSIVE,
            'detail' => $balanced
                ? __('citizen.verification.detail.shares_sum_to_whole')
                : __('citizen.verification.detail.shares_do_not_sum', [
                    'sum' => $numerator . '/' . $denominator,
                ]),
            'limitation' => __('citizen.verification.limitation.internal_records_only'),
            'is_automated' => true,
            'performed_at' => now(),
        ];
    }

    /**
     * Is there any recorded ownership chain for this plot at all?
     */
    protected function ownershipChainEvidence(VerificationRequest $request): array
    {
        if (! $request->plot_id) {
            return [
                'result' => VerificationCheck::RESULT_NOT_AVAILABLE,
                'detail' => __('citizen.verification.detail.no_plot_linked'),
                'limitation' => __('citizen.verification.limitation.internal_records_only'),
                'is_automated' => true,
                'performed_at' => now(),
            ];
        }

        $count = Plot::query()->find($request->plot_id)?->ownerships()->count() ?? 0;

        return [
            'result' => $count > 0
                ? VerificationCheck::RESULT_PASS
                : VerificationCheck::RESULT_INCONCLUSIVE,
            'detail' => $count > 0
                ? __('citizen.verification.detail.ownership_rows_found', ['count' => $count])
                : __('citizen.verification.detail.no_ownership_history'),
            'limitation' => __('citizen.verification.limitation.history_incomplete'),
            'is_automated' => true,
            'performed_at' => now(),
        ];
    }

    /**
     * Are there multiple live listings claiming the same land?
     *
     * This is the multiple-sale indicator. It compares PUBLISHED listings only,
     * which is all this build can see.
     */
    protected function duplicateSaleIndicators(VerificationRequest $request): array
    {
        if (! $request->khatian_no || ! $request->dag_no) {
            return $this->pending('duplicate_sale_indicators');
        }

        $listings = SaleListing::query()
            ->published()
            ->where('khatian_no', $request->khatian_no)
            ->where('dag_no', $request->dag_no)
            ->when($request->mouza_id, fn($q) => $q->where('mouza_id', $request->mouza_id))
            ->get();

        if ($listings->count() <= 1) {
            return [
                'result' => VerificationCheck::RESULT_PASS,
                'detail' => __('citizen.verification.detail.no_duplicate_listings'),
                'limitation' => __('citizen.verification.limitation.published_listings_only'),
                'is_automated' => true,
                'performed_at' => now(),
            ];
        }

        // Multiple live listings for the same land — a genuine warning sign.
        return [
            'result' => VerificationCheck::RESULT_FAIL,
            'detail' => __('citizen.verification.detail.multiple_live_listings', [
                'count' => $listings->count(),
            ]),
            'limitation' => __('citizen.verification.limitation.indicator_not_proof'),
            'is_automated' => true,
            'performed_at' => now(),
        ];
    }

    /**
     * A check requiring an external authority we do not integrate with.
     */
    protected function unavailableCheck(string $code): array
    {
        return [
            'result' => VerificationCheck::RESULT_NOT_AVAILABLE,
            'detail' => __('citizen.verification.detail.external_source_required'),
            'limitation' => __('citizen.verification.limitation.no_external_integration'),
            'is_automated' => true,
            'performed_at' => now(),
        ];
    }

    protected function unknownCheck(string $code): array
    {
        return [
            'result' => VerificationCheck::RESULT_PENDING,
            'detail' => __('citizen.verification.detail.unknown_check'),
            'limitation' => null,
            'is_automated' => true,
            'performed_at' => now(),
        ];
    }

    protected function pending(string $code): array
    {
        return [
            'result' => VerificationCheck::RESULT_PENDING,
            'detail' => __('citizen.verification.detail.insufficient_input'),
            'limitation' => __('citizen.verification.limitation.more_information_needed'),
            'is_automated' => true,
            'performed_at' => now(),
        ];
    }

    /**
     * A WARNING INDICATOR derived from check results. Not a fraud finding.
     *
     * Only failed checks and unavailable official verification contribute. A low
     * score is explicitly NOT a clean bill of health, because the authoritative
     * sources were never consulted.
     */
    public function riskIndicator(Collection $checks): int
    {
        $failed = $checks->where('result', VerificationCheck::RESULT_FAIL)->count();
        $inconclusive = $checks->where('result', VerificationCheck::RESULT_INCONCLUSIVE)->count();
        $unavailable = $checks->where('result', VerificationCheck::RESULT_NOT_AVAILABLE)->count();

        // A failed duplicate-sale check weighs most heavily.
        $score = ($failed * 25) + ($inconclusive * 10) + ($unavailable * 3);

        return max(0, min(100, $score));
    }

    /**
     * Build the explicit statement of what this verification did NOT establish.
     */
    public function limitationsStatement(): string
    {
        return __('citizen.verification.limitation.overall');
    }

    protected function lcm(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 1;
        }

        return intdiv(abs($a * $b), $this->gcd($a, $b));
    }

    protected function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return abs($a) ?: 1;
    }
}
