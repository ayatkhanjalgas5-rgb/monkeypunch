<?php

namespace App\Models;

use App\Models\Transaction;
use App\Models\WithdrawRequest;
use App\Observers\TelegramUserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

#[ObservedBy(TelegramUserObserver::class)]
class TelegramUser extends Authenticatable
{
    use HasApiTokens;

    protected $guarded = [];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'last_login_date' => 'datetime',
        'last_daily_booster_use' => 'datetime',
        'telegram_auth_date' => 'datetime',
        'is_premium' => 'boolean',
		'diamonds_balance' => 'integer',
    ];

    public function referrals()
    {
        return $this->hasMany(self::class, 'referred_by', 'telegram_id');
    }


    public function withdrawRequests()
    {
        return $this->hasMany(WithdrawRequest::class)->latest();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class)->latest();
    }

    public function dailyTasks()
    {
        return $this->belongsToMany(DailyTask::class, 'telegram_user_daily_tasks')
            ->withPivot('completed', 'created_at')
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'telegram_user_tasks')
            ->withPivot('is_submitted', 'is_rewarded', 'submitted_at')
            ->withTimestamps();
    }

    public function referralTasks()
    {
        return $this->belongsToMany(ReferralTask::class, 'telegram_user_referral_task')
            ->withPivot('is_completed')
            ->withTimestamps();
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function updateLoginStreak()
    {
        if (!$this->last_login_date?->isToday()) {
            $cap = DailyTask::count();
            $lastClaimedDailyTask = $this->dailyTasks()
                ->orderBy('telegram_user_daily_tasks.created_at', 'desc')
                ->first();

            if (
                $this->last_login_date?->isYesterday()
                && $lastClaimedDailyTask?->pivot?->created_at?->isYesterday()
                && $this->login_streak !== $cap
            ) {
                $this->login_streak = min($this->login_streak + 1, $cap);
            } else {
                $this->login_streak = 1;
                $this->dailyTasks()->detach();
            }
        }
    }


    public function calcPassiveEarning()
    {
        $passiveEarnings = 0;
        if ($this->last_login_date && $this->production_per_hour) {
            $threeHours = 3 * 60 * 60;
            $secondsPassed = (int) $this->last_login_date->diffInSeconds(now());
            if ($secondsPassed > $threeHours) $secondsPassed = $threeHours;
            $productionInSeconds = $this->production_per_hour / 3600;
            $passiveEarnings = $productionInSeconds * $secondsPassed;
            app(\App\Services\WalletService::class)->credit($this, $passiveEarnings, 'passive', ['reason' => 'passive_earnings']);
        }
        return $passiveEarnings;
    }

    public function tap($count = 1)
    {
        $count = max(0, (int) $count);
        $taps = min($count, max(0, (int) $this->available_energy));

        if ($taps <= 0) {
            return 0;
        }
        $multiplier = $this->getActiveBoosterMultiplier();
        $earned = $taps * $this->earn_per_tap * $multiplier;

        app(\App\Services\WalletService::class)->credit($this, $earned, 'tap', ['tap_count' => $taps, 'multiplier' => $multiplier]);
        $this->available_energy -= $taps;
        $this->last_tap_date = now();
        $this->save();

        return $earned;
    }

    private function getActiveBoosterMultiplier()
    {
        if ($this->booster_pack_7x) return 7;
        if ($this->booster_pack_3x) return 3;
        if ($this->booster_pack_2x) return 2;
        return 1;
    }

    public function restoreEnergy()
    {
        if ($this->max_energy === $this->available_energy) {
            return 0;
        }

        $now = now();
        $secondsPassed = abs($now->diffInSeconds($this->last_tap_date));

        $maxEnergy = $this->max_energy;

        $energyToRestore = min($secondsPassed, $maxEnergy);

        $this->available_energy = round(min($this->available_energy + $energyToRestore, $maxEnergy));
        $this->last_tap_date = $now;
        $this->save();

        return $energyToRestore;
    }


    public function canUseDailyBooster()
    {
        $now = now();

        // Check if it's a new day
        if (!$this->last_daily_booster_use || $this->last_daily_booster_use->addDay()->lte($now)) {
            return true;
        }

        // Check if an hour has passed since last use and total uses are less than 6
        return $this->daily_booster_uses < 6 && $this->last_daily_booster_use->addHour()->lte($now);
    }

    public function useDailyBooster()
    {
        if (!$this->canUseDailyBooster()) {
            return false;
        }

        $now = now();

        // Reset uses if it's a new day
        if (!$this->last_daily_booster_use || $this->last_daily_booster_use->addDay()->lte($now)) {
            $this->daily_booster_uses = 0;
        }

        $this->daily_booster_uses++;
        $this->last_daily_booster_use = $now;
        $this->available_energy = $this->max_energy; // Regenerate all energy
        $this->save();

        return true;
    }

    public function checkAndResetDailyBooster()
    {
        $now = now();

        if (!$this->last_daily_booster_use || $this->last_daily_booster_use->addDay()->lte($now)) {
            $this->daily_booster_uses = 0;
            $this->last_daily_booster_use = null;
            $this->save();
        }
    }
	public function weeklyRewardLogs()
{
    return $this->hasMany(\App\Models\WeeklyRewardLog::class);
}

}
