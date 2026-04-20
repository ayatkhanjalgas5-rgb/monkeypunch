<?php

namespace App\Observers;

use App\Models\Level;
use App\Models\TelegramUser;
use App\Services\WalletService;

class TelegramUserObserver
{
    public function updated(TelegramUser $user): void
    {
        if (!$user->wasChanged('balance')) {
            return;
        }

        $user->loadMissing('level');

        $currentLevelNumber = $user->level?->level ?? 0;

        $newLevels = Level::query()
            ->where('from_balance', '<=', $user->balance)
            ->where('level', '>', $currentLevelNumber)
            ->orderByDesc('level')
            ->get();

        $nextLevel = $newLevels->first();

        if (!$nextLevel) {
            return;
        }

        $levelUp = config('clicker.level_up');

        $user->level_id = $nextLevel->id;
        $user->max_energy += $newLevels->count() * ($levelUp['max_energy'] ?? 0);
        $user->earn_per_tap += $newLevels->count() * ($levelUp['earn_per_tap'] ?? 0);
        $user->saveQuietly();

        if (!$user->referred_by || (string) $user->referred_by === (string) $user->telegram_id) {
            return;
        }

        $referredBy = TelegramUser::where('telegram_id', $user->referred_by)->first();

        if (!$referredBy || $nextLevel->level <= 1) {
            return;
        }

        if ($user->is_suspicious || ($user->created_ip && $referredBy->created_ip && $user->created_ip === $referredBy->created_ip)) {
            return;
        }

        $limits = config('clicker.referral.limits', []);
        $maxReferralsPerIpPerReferrer = (int) ($limits['max_referrals_per_ip_per_referrer'] ?? 1);

        $ipReferralCount = TelegramUser::where('created_ip', $user->created_ip)
            ->where('referred_by', $referredBy->telegram_id)
            ->count();

        if ($ipReferralCount > $maxReferralsPerIpPerReferrer) {
            return;
        }

        $referralConfig = $referredBy->is_premium
            ? config('clicker.referral.premium.levelUp', [])
            : config('clicker.referral.base.levelUp', []);

        $reward = (int) ($referralConfig[$nextLevel->level] ?? 0);

        if ($reward > 0) {
            app(WalletService::class)->credit($referredBy, $reward, 'referral_level_up', [
                'from_telegram_user_id' => $user->id,
                'from_level' => $nextLevel->level,
            ]);
        }
    }
}
