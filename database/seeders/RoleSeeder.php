<?php

namespace Database\Seeders;

use App\Models\Role;
use App\RoleCode;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleCode::cases() as $code) {
            Role::updateOrCreate(['code' => $code->value], ['name' => $code->label()]);
        }
    }
}
