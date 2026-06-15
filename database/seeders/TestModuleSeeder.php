<?php

namespace Database\Seeders;

use App\Models\TestModule;
use App\Models\TestSpec;
use Illuminate\Database\Seeder;

class TestModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Pre-load spec IDs keyed by runner_key so we resolve by key, not by fragile numeric ID
        $specIds = TestSpec::all()->pluck('id', 'runner_key');

        $modules = [
            [
                'module_key'   => 'account_mgmt',
                'display_name' => 'Account Management',
                'description'  => 'Non-IDA account creation and management flows',
                'spec_key'     => 'account_mgmt_v2',
            ],
            [
                'module_key'   => 'lead_mgmt',
                'display_name' => 'Lead Management',
                'description'  => 'Lead creation, conversion, and list view tests',
                'spec_key'     => 'lead_mgmt',
            ],
            [
                'module_key'   => 'oppty_mgmt_sales',
                'display_name' => 'Opportunity Management - Sales',
                'description'  => 'Opportunity creation, product addition, and pricing tests',
                'spec_key'     => 'oppty_mgmt_sales',
            ],
            [
                'module_key'   => 'oppty_mgmt_es',
                'display_name' => 'Opportunity Management - ES',
                'description'  => 'Opportunity management for ES team',
                'spec_key'     => 'oppty_mgmt_es',
            ],
            [
                'module_key'   => 'quote_mgmt',
                'display_name' => 'Quote Management',
                'description'  => 'Quote creation and CPQ configuration tests',
                'spec_key'     => 'quote_mgmt_es',
            ],
            [
                'module_key'   => 'contract_mgmt',
                'display_name' => 'Contract - Order - Asset',
                'description'  => 'Contract management-Order-Service Delivery',
                'spec_key'     => 'contract_order_sd',
            ],
            [
                'module_key'   => 'contact_mgmt',
                'display_name' => 'Contact Management',
                'description'  => 'Contact creation',
                'spec_key'     => null,
            ],
        ];

        foreach ($modules as $data) {
            TestModule::updateOrCreate(
                ['module_key' => $data['module_key']],
                [
                    'display_name' => $data['display_name'],
                    'description'  => $data['description'],
                    'spec_id'      => $data['spec_key'] ? ($specIds[$data['spec_key']] ?? null) : null,
                ]
            );
        }

        $this->command->info('Seeded ' . count($modules) . ' test modules.');
    }
}
