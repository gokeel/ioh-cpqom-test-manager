<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductTestSuite;
use App\Models\TestModule;
use Illuminate\Database\Seeder;

class ProductTestSuiteSeeder extends Seeder
{
    // Same sequence for every product suite — matches the reference at /product-test-suites/1
    private array $moduleSequence = [
        1 => 'account_mgmt',
        2 => 'lead_mgmt',
        3 => 'oppty_mgmt_sales',
        4 => 'oppty_mgmt_es',
        5 => 'quote_mgmt',
        6 => 'contract_mgmt',
    ];

    public function run(): void
    {
        // Pre-load module IDs keyed by module_key
        $modules = TestModule::whereIn('module_key', array_values($this->moduleSequence))
            ->pluck('id', 'module_key');

        $pivot = [];
        foreach ($this->moduleSequence as $order => $key) {
            if (isset($modules[$key])) {
                $pivot[$modules[$key]] = ['sequence_order' => $order];
            } else {
                $this->command->warn("TestModule [{$key}] not found — skipping from sequence.");
            }
        }

        $products = Product::all();

        foreach ($products as $product) {
            $suite = ProductTestSuite::firstOrCreate(
                ['product_id' => $product->id],
                ['name' => $product->product_offer, 'description' => null]
            );

            $suite->modules()->sync($pivot);
        }

        $this->command->info("Seeded {$products->count()} product test suites.");
    }
}
