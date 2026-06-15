<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestSuiteSeeder extends Seeder
{
    public function run(): void
    {
        // Modules are seeded by TestModuleSeeder (must run before this).
        // Test parameters are per-user and cannot be seeded generically.

        // ── Runtime State ──────────────────────────────────────────────────
        $runtimeState = [
            [
                'state_key'   => 'opportunityId',
                'state_value' => '006MS000008yNRqYAM',
                'description' => 'Salesforce Opportunity ID — set after lead-conversion test; consumed by oppty_mgmt and quote_mgmt specs',
            ],
        ];

        foreach ($runtimeState as $state) {
            DB::table('runtime_state')->updateOrInsert(
                ['state_key' => $state['state_key']],
                array_merge($state, ['last_updated_at' => now()])
            );
        }

        $this->command->info('✓ TestSuiteSeeder: seeded runtime_state.');
    }
}
