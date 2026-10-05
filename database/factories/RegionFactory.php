<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Region> */
class RegionFactory extends Factory
{
    public function definition(): array
    {
        $slug = fake()->unique()->slug();

        return ['name' => $slug, 'slug' => $slug, 'kind' => 'cultural', 'active' => true];
    }
}
