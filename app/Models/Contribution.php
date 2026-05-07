<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contribution extends Model
{
    use HasFactory;

    public const TYPE_PLACE_SUGGESTION = 'place_suggestion';

    public const TYPE_PHOTO = 'photo';

    public const TYPE_CORRECTION = 'correction';

    public const TYPE_STORY_PROPOSAL = 'story_proposal';

    public const STATUS_PENDING = 'pending';

    public const STATUS_AUTO_APPROVED = 'auto_approved';

    public const STATUS_MANUAL_REVIEW = 'manual_review';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_MERGED = 'merged';

    protected $fillable = [
        'user_id',
        'type',
        'payload',
        'target_place_id',
        'target_story_id',
        'ai_score',
        'ai_reasoning',
        'ai_model',
        'ai_prompt_version',
        'status',
        'reviewer_id',
        'reviewed_at',
        'reviewer_notes',
        'submitted_ip',
        'submitted_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'ai_reasoning' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'submitted_ip',
        'submitted_user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetPlace(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'target_place_id');
    }

    public function targetStory(): BelongsTo
    {
        return $this->belongsTo(Story::class, 'target_story_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
