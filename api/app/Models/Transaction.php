<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function adminUser()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
