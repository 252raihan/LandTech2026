<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'label_en' => fake()->words(2, true),
            'label_bn' => 'ভূমিকা',
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn() => [
            'name' => $name,
            'label_en' => ucwords(str_replace('_', ' ', $name)),
        ]);
    }
}
