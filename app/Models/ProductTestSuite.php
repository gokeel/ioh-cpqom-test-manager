<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductTestSuite extends Model
{
    protected $fillable = ['name', 'description', 'product_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(TestModule::class, 'product_test_suite_modules')
            ->using(ProductTestSuiteModule::class)
            ->withPivot('sequence_order', 'id')
            ->orderByPivot('sequence_order');
    }
}
