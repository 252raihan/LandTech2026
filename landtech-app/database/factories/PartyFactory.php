<?php

namespace Database\Factories;

use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    protected $model = Party::class;

    public function definition(): array
    {
        return [
            'party_type' => Party::TYPE_INDIVIDUAL,
            'display_name_en' => fake()->name(),
            'display_name_bn' => 'সিমুলেটেড নাম',
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'status' => Party::STATUS_ACTIVE,
        ];
    }

    public function organization(): static
    {
        return $this->state(fn() => [
            'party_type' => Party::TYPE_ORGANIZATION,
            'display_name_en' => fake()->company(),
            'org_registration_no' => 'SIM-ORG-' . fake()->unique()->numberBetween(1000, 9999),
            'date_of_birth' => null,
            'gender' => null,
        ]);
    }
}
