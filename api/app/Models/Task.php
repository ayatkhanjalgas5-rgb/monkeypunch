<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = [];

    public function getImageAttribute($value)
    {
        return $value ? env("APP_STORAGE_URL", "/") . 'storage/' . $value : null;
    }

    public function telegramUsers()
    {
        return $this->belongsToMany(TelegramUser::class, 'telegram_user_tasks')
            ->withPivot('status', 'rewarded')
            ->withTimestamps();
    }
}
