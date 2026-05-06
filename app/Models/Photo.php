<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'uploadable_type',
        'uploadable_id',
        'filename',
        'path',
        'disk',
        'mime_type',
        'size',
        'width',
        'height',
        'variants',
        'uploaded_by',
        'license',
        'credit',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
        ];
    }

    public function uploadable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
