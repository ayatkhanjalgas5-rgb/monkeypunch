<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyLeaderboardCycle extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_closed' => 'boolean',
    ];

    public function rewards()
    {
        return $this->hasMany(WeeklyRewardLog::class);
    }
}
