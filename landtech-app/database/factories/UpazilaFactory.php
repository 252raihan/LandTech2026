<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Upazila;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Upazila>
 */
class UpazilaFactory extends Factory
{
    protected $model = Upazila::class;

    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'code' => 'SIM-UZ-' . fake()->unique()->numberBetween(1000, 9999),
            'name_en' => fake()->city(),
            'name_bn' => 'সিমুলেটেড উপজেলা',
        ];
    }
}
