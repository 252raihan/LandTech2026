<?php

namespace Database\Factories;

use App\Models\LandRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandRecord>
 */
class LandRecordFactory extends Factory
{
    protected $model = LandRecord::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'khatian_no' => 'SIM-KH-' . $this->faker->unique()->numberBetween(1000, 9999),
            'dag_no' => 'SIM-DG-' . $this->faker->unique()->numberBetween(1000, 9999),
            'survey_type' => 'BS',
            'area' => 12.5000,
            'area_unit' => 'decimal',
            'claim_share_numerator' => 1,
            'claim_share_denominator' => 1,
            'ownership_basis' => LandRecord::BASIS_PURCHASE,
            'mutation_status' => LandRecord::MUTATION_UNKNOWN,
            'intended_use' => LandRecord::INTENT_RECORD_KEEPING,
            // Always starts unverified: a factory must not create a verified
            // record by default, or tests could pass for the wrong reason.
            'status' => LandRecord::STATUS_DRAFT,
        ];
    }

    /**
     * A record that has been submitted and is awaiting review.
     */
    public function submitted(): static
    {
        return $this->state(fn() => [
            'status' => LandRecord::STATUS_PENDING_VERIFICATION,
            'submitted_at' => now(),
        ]);
    }
}
