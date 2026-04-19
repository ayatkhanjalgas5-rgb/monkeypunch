<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ReferralTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'number_of_referrals',
        'reward',
        'is_completed',
        'is_active'
    ];
}
