<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RuntimeStateSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            [
                'state_key'   => 'opportunityId',
                'state_value' => '006MR000009hLiMYAU',
                'description' => 'Salesforce Opportunity ID — set after lead-conversion test; consumed by oppty_mgmt and quote_mgmt specs',
            ],
            [
                'state_key'   => 'cartId',
                'state_value' => '0Q0MR000001SJWz0AO',
                'description' => null,
            ],
            [
                'state_key'   => 'quoteId',
                'state_value' => '0Q0MR000001SJWz0AO',
                'description' => null,
            ],
            [
                'state_key'   => 'corporateAccountId',
                'state_value' => '001MR00000BwJIUYA3',
                'description' => 'ID of corporate customer account (Brand record type)',
            ],
            [
                'state_key'   => 'billingContactId',
                'state_value' => '003MR00000AjnX1YAJ',
                'description' => 'Billing contact ID in Salesforce',
            ],
            [
                'state_key'   => 'customerAccountId',
                'state_value' => '001MR00000BwHwmYAF',
                'description' => 'ID of customer account (business record type)',
            ],
        ];

        foreach ($states as $state) {
            DB::table('runtime_state')->updateOrInsert(
                ['state_key' => $state['state_key']],
                array_merge($state, ['last_updated_at' => now()])
            );
        }

        $this->command->info('Seeded ' . count($states) . ' runtime state entries.');
    }
}
