<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductTestSuiteModule extends Pivot
{
    public $incrementing = true;

    protected $fillable = ['product_test_suite_id', 'test_module_id', 'sequence_order'];
}
