<?php

namespace App\Actions;

use App\Models\RuntimeState;
use App\Models\TestModule;
use App\Models\TestParameter;
use App\Models\User;

class SeedUserData
{
    public function __invoke(User $user): void
    {
        $this->seedRuntimeState($user);
        $this->seedTestParameters($user);
    }

    // ── Runtime State ──────────────────────────────────────────────────────────

    private function seedRuntimeState(User $user): void
    {
        $states = [
            [
                'state_key'   => 'opportunityId',
                'state_value' => '',
                'description' => 'Salesforce Opportunity ID — set after lead-conversion test; consumed by oppty_mgmt and quote_mgmt specs',
            ],
            [
                'state_key'   => 'cartId',
                'state_value' => '',
                'description' => null,
            ],
            [
                'state_key'   => 'quoteId',
                'state_value' => '',
                'description' => null,
            ],
            [
                'state_key'   => 'corporateAccountId',
                'state_value' => '',
                'description' => 'ID of corporate customer account (Brand record type)',
            ],
            [
                'state_key'   => 'billingContactId',
                'state_value' => '',
                'description' => 'Billing contact ID in Salesforce',
            ],
            [
                'state_key'   => 'customerAccountId',
                'state_value' => '',
                'description' => 'ID of customer account (business record type)',
            ],
        ];

        foreach ($states as $state) {
            RuntimeState::updateOrCreate(
                ['user_id' => $user->id, 'state_key' => $state['state_key']],
                array_merge($state, ['user_id' => $user->id, 'last_updated_at' => now()])
            );
        }
    }

    // ── Test Parameters ────────────────────────────────────────────────────────

    private function seedTestParameters(User $user): void
    {
        $moduleIds = TestModule::all()->pluck('id', 'module_key');

        $parameters = [

            // account_mgmt
            ['module_key' => 'account_mgmt', 'test_case_id' => 'tc001', 'parameters' => ['accountName' => 'AT Test CCA', 'phone' => 25567889, 'shippingStreet' => 'Jl. Pisang']],
            ['module_key' => 'account_mgmt', 'test_case_id' => 'tc002', 'parameters' => ['accountName' => 'AT Test CA', 'idReference' => 123456789012, 'phone' => 25567889, 'accountOption' => 'AT Test CA']],
            ['module_key' => 'account_mgmt', 'test_case_id' => 'tc_quote', 'parameters' => ['productCode' => 'O_AI_KNOW']],

            // contact_mgmt
            ['module_key' => 'contact_mgmt', 'test_case_id' => 'tc_contact', 'parameters' => ['firstName' => 'AT', 'lastName' => 'Kontak']],

            // contract_mgmt
            ['module_key' => 'contract_mgmt', 'test_case_id' => 'tc_quote', 'parameters' => ['productCode' => 'O_AI_KNOW']],

            // lead_mgmt
            ['module_key' => 'lead_mgmt', 'test_case_id' => 'tc001', 'parameters' => ['listViewName' => 'All my Lead', 'expectedColumns' => ['Project Name', 'Created By Alias']]],
            ['module_key' => 'lead_mgmt', 'test_case_id' => 'tc002', 'parameters' => [
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
            ]],
            ['module_key' => 'lead_mgmt', 'test_case_id' => 'tc008', 'parameters' => []],
            ['module_key' => 'lead_mgmt', 'test_case_id' => 'tc_quote', 'parameters' => ['productCode' => 'O_AI_KNOW']],

            // oppty_mgmt_es
            ['module_key' => 'oppty_mgmt_es', 'test_case_id' => 'tc_quote', 'parameters' => ['productCode' => 'O_AI_KNOW']],

            // oppty_mgmt_sales
            ['module_key' => 'oppty_mgmt_sales', 'test_case_id' => 'tc002', 'parameters' => ['accountName' => 'Test CA', 'idReference' => '123456789012', 'phone' => '25567889']],
            ['module_key' => 'oppty_mgmt_sales', 'test_case_id' => 'tc010', 'parameters' => [
                'productName'           => 'Alibaba Cloud IDR',
                'otc'                   => '5000000',
                'mrc'                   => '10000000',
                'expectedOtc'           => 'IDR 5,000,000',
                'expectedMrc'           => 'IDR 10,000,000',
                'expectedTotal'         => 'IDR 245,000,000',
                'expectedAnnualRevenue' => 'IDR 122,500,000',
            ]],
            ['module_key' => 'oppty_mgmt_sales', 'test_case_id' => 'tc_quote', 'parameters' => ['productCode' => 'O_AI_KNOW']],

            // quote_mgmt
            ['module_key' => 'quote_mgmt', 'test_case_id' => 'tc_quote', 'parameters' => [
                'productCode'          => 'O_AI_KNOW',
                'productName'          => 'AI Contact Center',
                'quantity'             => 1,
                'rc_override'          => 7000000,
                'otc_override'         => 30000000,
                'override_pricing'     => 'true',
                'randomize_attributes' => 'true',
            ]],
        ];

        foreach ($parameters as $row) {
            $moduleId = $moduleIds[$row['module_key']] ?? null;
            if (! $moduleId) {
                continue;
            }

            TestParameter::updateOrCreate(
                ['module_id' => $moduleId, 'test_case_id' => $row['test_case_id'], 'user_id' => $user->id],
                ['parameters' => $row['parameters']]
            );
        }
    }
}
