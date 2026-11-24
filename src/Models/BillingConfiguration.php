<?php

namespace SoftwareBilling\Models;

use Illuminate\Database\Eloquent\Model;

class BillingConfiguration extends Model
{
    protected $table = 'bill_configuration';

    protected $fillable = [
        'api_url',
        'api_token',
        'fetch_interval',
        'last_fetch_at',
        'status',
        'additional_config',
    ];

    protected $casts = [
        'last_fetch_at' => 'datetime',
        'status' => 'integer',
        'fetch_interval' => 'integer',
    ];
}