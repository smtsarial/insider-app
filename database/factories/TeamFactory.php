<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'short_name' => strtoupper(fake()->lexify('???')),
            'power' => fake()->numberBetween(60, 95),
            'supporter_factor' => 10,
            'goalkeeper_factor' => 10,
            'sort_order' => 0,
        ];
    }
}
