<?php

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<State> */
class StateFactory extends Factory
{
    public function definition(): array
    {
        $code = (string) fake()->unique()->numberBetween(10, 99);

        return ['ibge_code' => $code, 'abbreviation' => fake()->unique()->lexify('??'), 'name' => 'Estado '.$code, 'slug' => 'estado-'.$code, 'active' => true];
    }
}
