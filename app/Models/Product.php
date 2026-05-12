<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['product_offer', 'product_code', 'product_line'];

    public function productTestSuites(): HasMany
    {
        return $this->hasMany(ProductTestSuite::class);
    }
}
