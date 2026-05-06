<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiRun extends Model
{
    use HasFactory;

    public const TYPE_BRIEF_WEEKLY = 'brief_weekly';
    public const TYPE_STORY_GENERATION = 'story_generation';
    public const TYPE_CONTRIBUTION_MODERATION = 'contribution_moderation';
    public const TYPE_EVENT_CATEGORIZATION = 'event_categorization';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_TIMEOUT = 'timeout';

    protected $fillable = [
        'type',
        'model_used',
        'prompt_template_version',
        'input_tokens',
        'output_tokens',
        'cost_usd',
        'duration_ms',
        'status',
        'error_message',
        'related_type',
        'related_id',
    ];

    protected function casts(): array
    {
        return [
            'cost_usd' => 'decimal:6',
        ];
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
