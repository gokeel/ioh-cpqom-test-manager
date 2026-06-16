<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestSuiteSeeder extends Seeder
{
    public function run(): void
    {
        // Modules        → TestModuleSeeder
        // Test parameters → per-user; cannot be seeded generically
        // Runtime state  → RuntimeStateSeeder

        $this->command->info('TestSuiteSeeder: nothing to seed (all responsibilities delegated).');
    }
}
