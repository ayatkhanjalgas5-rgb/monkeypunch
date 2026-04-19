<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyRewardLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }

    public function cycle()
    {
        return $this->belongsTo(WeeklyLeaderboardCycle::class);
    }
}
