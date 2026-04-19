<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BattleRoom extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'finished_at' => 'datetime',
        'search_started_at' => 'datetime',
        'countdown_started_at' => 'datetime',
        'player_one_ready_at' => 'datetime',
        'player_two_ready_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'stake_amount' => 'integer',
        'winner_reward' => 'integer',
        'loser_reward' => 'integer',
        'fee_amount' => 'integer',
        'player_one_score' => 'integer',
        'player_two_score' => 'integer',
        'player_one_support_spent' => 'integer',
        'player_two_support_spent' => 'integer',
    ];

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function playerOne()
    {
        return $this->belongsTo(TelegramUser::class, 'player_one_id');
    }

    public function playerTwo()
    {
        return $this->belongsTo(TelegramUser::class, 'player_two_id');
    }

    public function winner()
    {
        return $this->belongsTo(TelegramUser::class, 'winner_id');
    }
}
