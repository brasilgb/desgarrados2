<?php

namespace Database\Factories;

use App\Models\Municipality;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Municipality> */
class MunicipalityFactory extends Factory
{
    public function definition(): array
    {
        $code = (string) fake()->unique()->numberBetween(1000000, 9999999);

        return ['state_id' => State::factory(), 'ibge_code' => $code, 'name' => 'Município '.$code, 'slug' => 'municipio-'.$code, 'active' => true];
    }
}
