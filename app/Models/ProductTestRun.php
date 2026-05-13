<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTestRun extends Model
{
    protected $fillable = [
        'product_test_suite_id',
        'test_module_id',
        'user_id',
        'status',
        'log',
        'runner_response',
        'created_ids',
        'validation_status',
        'finding',
        'evidence_images',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'runner_response' => 'array',
        'created_ids'     => 'array',
        'evidence_images' => 'array',
        'started_at'      => 'datetime',
        'finished_at'     => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function suite(): BelongsTo
    {
        return $this->belongsTo(ProductTestSuite::class, 'product_test_suite_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TestModule::class, 'test_module_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['success', 'error', 'aborted']);
    }
}
