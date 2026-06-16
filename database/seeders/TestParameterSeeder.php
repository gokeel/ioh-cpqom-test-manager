<?php

namespace Database\Seeders;

use App\Models\TestModule;
use App\Models\TestParameter;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestParameterSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@salesforce-test-manager.com')->firstOrFail();

        // Keyed by module_key for lookup
        $moduleIds = TestModule::all()->pluck('id', 'module_key');

        $parameters = [

            // ── Account Management ────────────────────────────────────────────
            [
                'module_key'   => 'account_mgmt',
                'test_case_id' => 'tc001',
                'parameters'   => ['accountName' => 'AT Test CCA', 'phone' => 25567889, 'shippingStreet' => 'Jl. Pisang'],
            ],
            [
                'module_key'   => 'account_mgmt',
                'test_case_id' => 'tc002',
                'parameters'   => ['accountName' => 'AT Test CA', 'idReference' => 123456789012, 'phone' => 25567889, 'accountOption' => 'AT Test CA'],
            ],
            [
                'module_key'   => 'account_mgmt',
                'test_case_id' => 'tc_quote',
                'parameters'   => ['productCode' => 'O_AI_KNOW'],
            ],

            // ── Contact Management ────────────────────────────────────────────
            [
                'module_key'   => 'contact_mgmt',
                'test_case_id' => 'tc_contact',
                'parameters'   => ['firstName' => 'AT', 'lastName' => 'Kontak'],
            ],

            // ── Contract Management ───────────────────────────────────────────
            [
                'module_key'   => 'contract_mgmt',
                'test_case_id' => 'tc_quote',
                'parameters'   => ['productCode' => 'O_AI_KNOW'],
            ],

            // ── Lead Management ───────────────────────────────────────────────
            [
                'module_key'   => 'lead_mgmt',
                'test_case_id' => 'tc001',
                'parameters'   => ['listViewName' => 'All my Lead', 'expectedColumns' => ['Project Name', 'Created By Alias']],
            ],
            [
                'module_key'   => 'lead_mgmt',
                'test_case_id' => 'tc002',
                'parameters'   => [
                    'accountName'          => 'AT TEST CA 38',
                    'accountOption'        => 'AT TEST CA 38',
                    'rfsDateMonthsAhead'   => 2,
                    'rfsDateDay'           => 10,
                    'projectName'          => 'Mantap bos',
                    'company'              => 'Pertamax Bos',
                    'leadSource'           => 'Indosat Vendor Data',
                    'description'          => 'Created by Automation Testing',
                    'leadCurrency'         => 'IDR - Indonesian Rupiah',
                    'primaryContactSearch' => 'AT Kontak 10',
                    'primaryContactOption' => 'AT Kontak 10',
                    'lastName'             => 'Agus',
                    'mobile'               => 817456789,
                    'typeOfProduct'        => 'Connectivity',
                    'function'             => 'IT',
                    'budgetStatus'         => 'Budget available',
                    'roleOfLeadSeniority'  => 'Enterprise (Director / Vice',
                    'timeframe'            => '-3 Months',
                    'newRequirements'      => 'Yes',
                    'existingCustomer'     => 'Yes',
                    'leadType'             => 'Customer/End User',
                    'expectedLeadOwner'    => 'OCKY HARLIANSYAH',
                    'expectedLeadStatus'   => 'New',
                ],
            ],
            [
                'module_key'   => 'lead_mgmt',
                'test_case_id' => 'tc008',
                'parameters'   => [],
            ],
            [
                'module_key'   => 'lead_mgmt',
                'test_case_id' => 'tc_quote',
                'parameters'   => ['productCode' => 'O_AI_KNOW'],
            ],

            // ── Opportunity Management - ES ───────────────────────────────────
            [
                'module_key'   => 'oppty_mgmt_es',
                'test_case_id' => 'tc_quote',
                'parameters'   => ['productCode' => 'O_AI_KNOW'],
            ],

            // ── Opportunity Management - Sales ────────────────────────────────
            [
                'module_key'   => 'oppty_mgmt_sales',
                'test_case_id' => 'tc002',
                'parameters'   => ['accountName' => 'Test CA', 'idReference' => '123456789012', 'phone' => '25567889'],
            ],
            [
                'module_key'   => 'oppty_mgmt_sales',
                'test_case_id' => 'tc010',
                'parameters'   => [
                    'productName'           => 'Alibaba Cloud IDR',
                    'otc'                   => '5000000',
                    'mrc'                   => '10000000',
                    'expectedOtc'           => 'IDR 5,000,000',
                    'expectedMrc'           => 'IDR 10,000,000',
                    'expectedTotal'         => 'IDR 245,000,000',
                    'expectedAnnualRevenue' => 'IDR 122,500,000',
                ],
            ],
            [
                'module_key'   => 'oppty_mgmt_sales',
                'test_case_id' => 'tc_quote',
                'parameters'   => ['productCode' => 'O_AI_KNOW'],
            ],

            // ── Quote Management ──────────────────────────────────────────────
            [
                'module_key'   => 'quote_mgmt',
                'test_case_id' => 'tc_quote',
                'parameters'   => [
                    'productCode'          => 'O_AI_KNOW',
                    'productName'          => 'AI Contact Center',
                    'quantity'             => 1,
                    'rc_override'          => 7000000,
                    'otc_override'         => 30000000,
                    'override_pricing'     => 'true',
                    'randomize_attributes' => 'true',
                ],
            ],
        ];

        $seeded = 0;
        foreach ($parameters as $row) {
            $moduleId = $moduleIds[$row['module_key']] ?? null;

            if (! $moduleId) {
                $this->command->warn("Module [{$row['module_key']}] not found — skipping {$row['test_case_id']}.");
                continue;
            }

            TestParameter::updateOrCreate(
                [
                    'module_id'    => $moduleId,
                    'test_case_id' => $row['test_case_id'],
                    'user_id'      => $admin->id,
                ],
                [
                    'parameters' => $row['parameters'],
                    'notes'      => $row['notes'] ?? null,
                ]
            );

            $seeded++;
        }

        $this->command->info("Seeded {$seeded} test parameters for [{$admin->email}].");
    }
}
