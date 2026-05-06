<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entitlement extends Model
{
    use HasFactory;

    public const TIER_FREE = 'free';
    public const TIER_PLUS = 'plus';
    public const TIER_PATRON = 'patron';

    protected $fillable = [
        'code',
        'label',
        'description',
        'tier_required',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
