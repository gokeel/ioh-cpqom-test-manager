<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Admin User
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@salesforce-test-manager.com'],
            [
                'name' => 'System Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                'role' => 'Admin',
                'email_verified_at' => now(),
            ]
        );

        // Salesforce Modules
        $modules = [
            ['name' => 'Lead to Opportunity', 'description' => 'Manage the lead qualification and conversion process.'],
            ['name' => 'CPQ', 'description' => 'Configure, Price, Quote - manage complex pricing and product configurations.'],
            ['name' => 'Product Catalog', 'description' => 'Manage product definitions and catalogs.'],
            ['name' => 'Contract Lifecycle Management', 'description' => 'Creation, negotiation, and lifecycle management of contracts.'],
            ['name' => 'Order Management', 'description' => 'Order capture, fulfillment, and orchestration.'],
            ['name' => 'Case Management', 'description' => 'Service Cloud issue tracking and resolution.'],
        ];

        foreach ($modules as $mod) {
            \App\Models\Module::firstOrCreate(['name' => $mod['name']], $mod);
        }

        // Test specs (Playwright spec files)
        $this->call(TestSpecSeeder::class);

        // Test modules (depends on specs being present)
        $this->call(TestModuleSeeder::class);

        // Runtime state
        $this->call(TestSuiteSeeder::class);

        // Product catalog
        $this->call(ProductSeeder::class);

        // Product test suites
        $this->call(ProductTestSuiteSeeder::class);
    }
}
