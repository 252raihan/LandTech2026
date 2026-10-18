<?php

namespace Database\Seeders;

use App\Models\Mouza;
use App\Models\Party;
use App\Models\PartyIdentity;
use App\Models\Plot;
use App\Models\PlotOwnership;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Phase 3 — synthetic parties, plots and ownerships.
 *
 * ALL DATA IS SYNTHETIC (LandTech 2026 rule book §8). No real persons, no real
 * NIDs, no real land records. Every identity number keeps the existing `SIM-`
 * prefix established by the frontend prototype, and both Bengali and ASCII
 * digit forms are exercised so the normalizer is covered by real seed data.
 *
 * No government API is contacted at any point.
 *
 * IDEMPOTENT: every record is matched on a stable natural key
 * (party display name, plot khatian+plot no, identity number, ownership
 * plot+party pair), so re-running the seeder updates in place instead of
 * duplicating. Existing primary keys are preserved, which keeps
 * `plots.mouza_id` and `plot_ownerships.*` references valid.
 *
 * PRIVILEGE SAFETY (Phase 4 / H1)
 * -------------------------------
 * This seeder no longer promotes "whichever user happens to be row 1". The
 * officer identity is passed in explicitly by DatabaseSeeder, and the role is
 * attached only when the application environment is local or testing. In any
 * other environment this seeder creates reference/domain data only and grants
 * no privileged role at all.
 */
class PartySeeder extends Seeder
{
    /**
     * @param  User|null  $registryOfficer  Explicit demo officer, or null to seed
     *                                      data with no privileged attribution.
     */
    public function run(?User $registryOfficer = null): void
    {
        $mouzas = Mouza::query()->orderBy('id')->get();

        if ($mouzas->isEmpty()) {
            // GeoSeeder has not run; nothing to attach plots to.
            return;
        }

        $parties = [
            [
                'attrs' => [
                    'party_type' => Party::TYPE_INDIVIDUAL,
                    'display_name_en' => 'Simulated Individual One',
                    'display_name_bn' => 'সিমুলেটেড ব্যক্তি এক',
                    'date_of_birth' => '1985-04-12',
                    'gender' => 'male',
                    'status' => Party::STATUS_ACTIVE,
                ],
                // ASCII digits.
                'identity' => ['type' => PartyIdentity::TYPE_NID, 'number' => 'SIM-100001'],
            ],
            [
                'attrs' => [
                    'party_type' => Party::TYPE_INDIVIDUAL,
                    'display_name_en' => 'Simulated Individual Two',
                    'display_name_bn' => 'সিমুলেটেড ব্যক্তি দুই',
                    'date_of_birth' => '1990-11-30',
                    'gender' => 'female',
                    'status' => Party::STATUS_ACTIVE,
                ],
                // BENGALI digits — exercises IdentityNormalizer end to end.
                'identity' => ['type' => PartyIdentity::TYPE_NID, 'number' => 'SIM-১০০২'],
            ],
            [
                'attrs' => [
                    'party_type' => Party::TYPE_INDIVIDUAL,
                    'display_name_en' => 'Simulated Heir Three',
                    'display_name_bn' => 'সিমুলেটেড উত্তরাধিকারী তিন',
                    'date_of_birth' => '1998-02-05',
                    'gender' => 'female',
                    'status' => Party::STATUS_ACTIVE,
                ],
                'identity' => ['type' => PartyIdentity::TYPE_NID, 'number' => 'SIM-100003'],
            ],
            [
                'attrs' => [
                    'party_type' => Party::TYPE_ORGANIZATION,
                    'display_name_en' => 'Simulated Housing Cooperative Ltd',
                    'display_name_bn' => 'সিমুলেটেড হাউজিং সমবায় লিমিটেড',
                    'legal_name_en' => 'Simulated Housing Cooperative Limited',
                    'legal_name_bn' => 'সিমুলেটেড হাউজিং সমবায় লিমিটেড',
                    'org_registration_no' => 'SIM-ORG-0001',
                    'status' => Party::STATUS_ACTIVE,
                ],
                'identity' => ['type' => PartyIdentity::TYPE_ORG_REG, 'number' => 'SIM-ORG-0001'],
            ],
        ];

        $created = [];

        foreach ($parties as $entry) {
            $party = Party::firstOrCreate(
                ['display_name_en' => $entry['attrs']['display_name_en']],
                $entry['attrs'] + [
                    'created_by' => $registryOfficer?->id,
                    'updated_by' => $registryOfficer?->id,
                ]
            );

            // Match the identity on its keyed HMAC so a re-run reuses the row
            // rather than tripping the unique (id_type, id_number_lookup_hmac)
            // index.
            $lookupHashes = array_values(PartyIdentity::lookupHashesFor($entry['identity']['number']));

            $identity = PartyIdentity::firstOrNew([
                'party_id' => $party->id,
                'id_type' => $entry['identity']['type'],
                'id_number_lookup_hmac' => $lookupHashes[0],
            ]);

            $identity->is_primary = true;
            $identity->verified_at = now();
            $identity->verified_by = $registryOfficer?->id;

            // Encrypts with APP_KEY and derives the keyed HMAC lookup hash.
            $identity->setIdentityNumber($entry['identity']['number']);
            $identity->save();

            $created[$party->display_name_en] = $party;
        }

        // Plots attached to the seeded geo hierarchy.
        $plots = [];

        foreach ($mouzas->take(2) as $index => $mouza) {
            $plots[] = Plot::firstOrCreate(
                [
                    'mouza_id' => $mouza->id,
                    'khatian_no' => 'SIM-KH-'.(100 + $index),
                    'plot_no' => 'SIM-PL-'.(200 + $index),
                ],
                [
                    'survey_type' => 'BS',
                    'area_decimal' => 33.5000 + $index,
                    'status' => Plot::STATUS_ACTIVE,
                    'created_by' => $registryOfficer?->id,
                    'updated_by' => $registryOfficer?->id,
                ]
            );
        }

        if (count($plots) < 2) {
            return;
        }

        // Plot 1: sole ownership (whole plot, 1/1).
        //
        // NOTE: `acquired_on` is cast to a date, so a firstOrCreate() keyed on it
        // compares a Carbon instance against the stored string and never matches.
        // We therefore resolve the existing row with whereDate() explicitly.
        $sole = PlotOwnership::query()
            ->where('plot_id', $plots[0]->id)
            ->where('party_id', $created['Simulated Individual One']->id)
            ->whereDate('acquired_on', '2019-06-01')
            ->first();

        if (! $sole) {
            PlotOwnership::create([
                'plot_id' => $plots[0]->id,
                'party_id' => $created['Simulated Individual One']->id,
                'ownership_role' => PlotOwnership::ROLE_SOLE,
                'share_numerator' => 1,
                'share_denominator' => 1,
                'acquired_on' => '2019-06-01',
                'status' => PlotOwnership::STATUS_CURRENT,
                'created_by' => $registryOfficer?->id,
                'updated_by' => $registryOfficer?->id,
            ]);
        }

        // Plot 2: fractional co-ownership — 1/3 + 1/3 + 1/3, exercising exact
        // fractions (never 0.3333) for future inheritance arithmetic.
        $coOwners = [
            $created['Simulated Individual Two'],
            $created['Simulated Heir Three'],
            $created['Simulated Housing Cooperative Ltd'],
        ];

        foreach ($coOwners as $party) {
            $existing = PlotOwnership::query()
                ->where('plot_id', $plots[1]->id)
                ->where('party_id', $party->id)
                ->whereDate('acquired_on', '2021-01-15')
                ->first();

            if (! $existing) {
                PlotOwnership::create([
                    'plot_id' => $plots[1]->id,
                    'party_id' => $party->id,
                    'ownership_role' => PlotOwnership::ROLE_HEIR_SHARE,
                    'share_numerator' => 1,
                    'share_denominator' => 3,
                    'acquired_on' => '2021-01-15',
                    'status' => PlotOwnership::STATUS_CURRENT,
                    'created_by' => $registryOfficer?->id,
                    'updated_by' => $registryOfficer?->id,
                ]);
            }
        }
    }
}
