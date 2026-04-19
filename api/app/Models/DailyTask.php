<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyTask extends Model
{
    protected $guarded = [];

    public function telegramUsers()
    {
        return $this->belongsToMany(TelegramUser::class, 'telegram_user_daily_tasks')
            ->withPivot('completed', 'created_at')
            ->withTimestamps();
    }
}
