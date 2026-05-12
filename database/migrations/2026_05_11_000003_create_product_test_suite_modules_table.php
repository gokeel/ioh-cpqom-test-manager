<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_test_suite_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_test_suite_id')->constrained('product_test_suites')->cascadeOnDelete();
            $table->foreignId('test_module_id')->constrained('test_modules')->cascadeOnDelete();
            $table->integer('sequence_order')->default(0);
            $table->timestamps();

            $table->unique(['product_test_suite_id', 'test_module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_test_suite_modules');
    }
};
