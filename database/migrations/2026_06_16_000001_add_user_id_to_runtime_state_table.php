<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1 — add nullable so existing rows don't violate NOT NULL
        Schema::table('runtime_state', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
        });

        // Step 2 — backfill existing rows to the first admin user
        $adminId = DB::table('users')->orderBy('id')->value('id');
        DB::table('runtime_state')->whereNull('user_id')->update(['user_id' => $adminId]);

        // Step 3 — make NOT NULL, drop old unique on state_key alone, add composite unique
        Schema::table('runtime_state', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->dropUnique(['state_key']);
            $table->unique(['state_key', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('runtime_state', function (Blueprint $table) {
            $table->dropUnique(['state_key', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->string('state_key', 100)->unique()->change();
        });
    }
};
