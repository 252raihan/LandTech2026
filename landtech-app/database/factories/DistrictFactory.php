<?php

namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    protected $model = District::class;

    public function definition(): array
    {
        return [
            'code' => 'SIM-DT-' . fake()->unique()->numberBetween(1000, 9999),
            'name_en' => fake()->city(),
            'name_bn' => 'সিমুলেটেড জেলা',
            'division_en' => 'Simulated Division',
            'division_bn' => 'সিমুলেটেড বিভাগ',
        ];
    }
}
