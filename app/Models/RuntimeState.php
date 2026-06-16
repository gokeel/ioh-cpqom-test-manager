<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuntimeState extends Model
{
    protected $table = 'runtime_state';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'state_key',
        'state_value',
        'description',
        'last_updated_at',
    ];

    protected $casts = [
        'last_updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function setValue(string $key, string $value, int $userId): self
    {
        return static::updateOrCreate(
            ['state_key' => $key, 'user_id' => $userId],
            ['state_value' => $value, 'last_updated_at' => now()]
        );
    }

    public static function getValue(string $key, int $userId, ?string $default = null): ?string
    {
        return static::where('state_key', $key)->where('user_id', $userId)->value('state_value') ?? $default;
    }
}
