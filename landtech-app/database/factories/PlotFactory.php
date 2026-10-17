<?php

namespace Database\Factories;

use App\Models\Mouza;
use App\Models\Plot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plot>
 */
class PlotFactory extends Factory
{
    protected $model = Plot::class;

    public function definition(): array
    {
        return [
            'mouza_id' => Mouza::factory(),
            'khatian_no' => 'SIM-KH-' . fake()->unique()->numberBetween(1000, 9999),
            'plot_no' => 'SIM-PL-' . fake()->unique()->numberBetween(1000, 9999),
            'survey_type' => 'BS',
            'area_decimal' => fake()->randomFloat(4, 1, 200),
            'status' => Plot::STATUS_ACTIVE,
        ];
    }
}
