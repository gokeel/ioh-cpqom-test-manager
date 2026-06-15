<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SfEnvironment extends Model
{
    protected $table = 'sf_environments';

    protected $fillable = [
        'persona_key',
        'sf_url',
        'after_login_url',
        'username',
        'password',
        'client_id',
        'client_secret',
    ];

    protected $casts = [
        'password'      => 'encrypted',
        'client_id'     => 'encrypted',
        'client_secret' => 'encrypted',
    ];
}
