<?php

namespace Database\Seeders;

use App\Models\RuntimeState;
use App\Models\User;
use Illuminate\Database\Seeder;

class RuntimeStateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@salesforce-test-manager.com')->firstOrFail();

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
            RuntimeState::updateOrCreate(
                ['state_key' => $state['state_key'], 'user_id' => $admin->id],
                array_merge($state, ['user_id' => $admin->id, 'last_updated_at' => now()])
            );
        }

        $this->command->info('Seeded ' . count($states) . ' runtime state entries for [' . $admin->email . '].');
    }
}
