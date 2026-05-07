<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'slug',
        'ip_hash',
        'referrer_host',
        'is_bot',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'viewed_at' => 'datetime',
        ];
    }
}
