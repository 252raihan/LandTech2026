<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Mouza;
use App\Models\Upazila;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mouza>
 */
class MouzaFactory extends Factory
{
    protected $model = Mouza::class;

    public function definition(): array
    {
        return [
            'upazila_id' => Upazila::factory(),
            'code' => 'SIM-MZ-' . fake()->unique()->numberBetween(1000, 9999),
            'name_en' => fake()->city(),
            'name_bn' => 'সিমুলেটেড মৌজা',
        ];
    }
}
