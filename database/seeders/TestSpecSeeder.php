<?php

namespace Database\Seeders;

use App\Models\TestSpec;
use Illuminate\Database\Seeder;

class TestSpecSeeder extends Seeder
{
    public function run(): void
    {
        $specs = [
            [
                'display_name' => 'Account Management',
                'runner_key'   => 'account_mgmt',
                'file_path'    => 'tests/non-ida/01-account-mgmt.spec.js',
                'description'  => null,
                'test_type'    => 'ui',
            ],
            [
                'display_name' => 'Lead Management',
                'runner_key'   => 'lead_mgmt',
                'file_path'    => 'tests/non-ida/02-lead-mgmt.spec.js',
                'description'  => 'General lead management',
                'test_type'    => 'ui',
            ],
            [
                'display_name' => 'Opportunity Management - Sales Profile',
                'runner_key'   => 'oppty_mgmt_sales',
                'file_path'    => 'tests/non-ida/03-oppty-mgmt-sales.spec.js',
                'description'  => 'Opportunity management for sales profile',
                'test_type'    => 'ui',
            ],
            [
                'display_name' => 'Opportunity Management - ES Profile',
                'runner_key'   => 'oppty_mgmt_es',
                'file_path'    => 'tests/non-ida/04-oppty-mgmt-es.spec.js',
                'description'  => 'Opportunity Management for enterprise solution team',
                'test_type'    => 'ui',
            ],
            [
                'display_name' => 'Quote Management - ES Profile',
                'runner_key'   => 'quote_mgmt_es',
                'file_path'    => 'tests/non-ida/05-quote-mgmt-es.spec.js',
                'description'  => 'Quote Management for enterprise solution team',
                'test_type'    => 'ui',
            ],
            [
                'display_name' => 'Quote Management',
                'runner_key'   => 'api_quote-management_1777389967',
                'file_path'    => null,
                'description'  => null,
                'test_type'    => 'api',
            ],
            [
                'display_name' => 'Account Management v2',
                'runner_key'   => 'account_mgmt_v2',
                'file_path'    => 'tests/non-ida/01-account-mgmt-api.spec.js',
                'description'  => null,
                'test_type'    => 'ui',
            ],
            [
                'display_name' => 'Contract_Order_SD',
                'runner_key'   => 'contract_order_sd',
                'file_path'    => 'tests/non-ida/06-contract-mgmt-sales.spec.js',
                'description'  => null,
                'test_type'    => 'ui',
            ],
        ];

        foreach ($specs as $spec) {
            TestSpec::updateOrCreate(
                ['runner_key' => $spec['runner_key']],
                $spec
            );
        }
    }
}
