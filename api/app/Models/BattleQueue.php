<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BattleQueue extends Model
{
    protected $table = 'battle_queue';

    protected $fillable = [
        'telegram_user_id',
        'room_tier',
        'stake_amount',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'stake_amount' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }
}
