<?php

namespace Database\Factories;

use App\Models\Role;
use App\RoleCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Role> */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $code = fake()->unique()->randomElement(RoleCode::cases());

        return ['code' => $code->value, 'name' => $code->label()];
    }
}
