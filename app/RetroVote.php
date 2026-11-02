<?php

namespace ProjectsManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetroVote extends Model
{
    protected $fillable = [
        'retro_card_id',
        'user_id',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(RetroCard::class, 'retro_card_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

