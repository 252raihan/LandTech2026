<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\Plot;
use App\Models\PlotOwnership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlotOwnership>
 */
class PlotOwnershipFactory extends Factory
{
    protected $model = PlotOwnership::class;

    public function definition(): array
    {
        return [
            'plot_id' => Plot::factory(),
            'party_id' => Party::factory(),
            'ownership_role' => PlotOwnership::ROLE_SOLE,
            'share_numerator' => 1,
            'share_denominator' => 1,
            'acquired_on' => fake()->dateTimeBetween('-20 years', 'now')->format('Y-m-d'),
            'disposed_on' => null,
            'status' => PlotOwnership::STATUS_CURRENT,
        ];
    }

    /**
     * Fractional co-ownership, e.g. ->fraction(1, 3).
     */
    public function fraction(int $numerator, int $denominator): static
    {
        return $this->state(fn() => [
            'ownership_role' => PlotOwnership::ROLE_HEIR_SHARE,
            'share_numerator' => $numerator,
            'share_denominator' => $denominator,
        ]);
    }
}
