<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackgroundJob extends Model
{
    protected $fillable = [
        'class',
        'method',
        'parameters',
        'status',
        'retry_count',
        'next_retry_at',
    ];

    protected $casts = [
        'parameters' => 'array', // Automatically cast JSON to array
        'next_retry_at' => 'datetime',
    ];
}
