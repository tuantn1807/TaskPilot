<?php

namespace ProjectsManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetroCard extends Model
{
    protected $fillable = [
        'retrospective_id',
        'user_id',
        'is_anonymous',
        'column_type',
        'content',
        'converted_task_id',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function retrospective(): BelongsTo
    {
        return $this->belongsTo(Retrospective::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function convertedTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'converted_task_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(RetroVote::class);
    }

    public function isVotedBy(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        if ($this->relationLoaded('votes')) {
            return $this->votes->contains('user_id', $userId);
        }

        return $this->votes()->where('user_id', $userId)->exists();
    }
}

