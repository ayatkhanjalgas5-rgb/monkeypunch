<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\TelegramUser;
use App\Services\TelegramInitDataService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private TelegramInitDataService $telegramInitDataService,
        private WalletService $walletService
    ) {
    }

    public function telegramUser(Request $request)
    {
        $ip = $request->ip();
        $limits = config('clicker.referral.limits', []);
        $maxAccountsPerIp = (int) ($limits['max_accounts_per_ip'] ?? 3);
        $maxReferralsPerIpPerReferrer = (int) ($limits['max_referrals_per_ip_per_referrer'] ?? 1);
        $suspiciousThreshold = (int) ($limits['suspicious_accounts_per_ip'] ?? 2);

        $initData = (string) ($request->header('X-Telegram-Init-Data') ?? $request->input('init_data', ''));
        $verifiedFromMiddleware = (bool) $request->attributes->get('telegram_init_data_verified', false);
        $allowUnverified = (bool) config('services.telegram.allow_unverified_local');
        $isVerified = $initData !== '' && ($verifiedFromMiddleware || $this->telegramInitDataService->verify($initData));

        if (!$isVerified && !$allowUnverified) {
            return response()->json([
                'message' => 'Telegram init data verification failed.',
            ], 403);
        }

        $payload = $initData !== ''
            ? ($request->attributes->get('telegram_init_data_payload') ?: $this->telegramInitDataService->parse($initData))
            : [];

        $telegramUser = is_array($payload['user'] ?? null) ? $payload['user'] : [];
        $telegramId = $telegramUser['id'] ?? $request->input('telegram_id');
        $firstName = $telegramUser['first_name'] ?? $request->input('first_name');

        $validated = validator([
            'telegram_id' => $telegramId,
            'first_name' => $firstName,
            'last_name' => $telegramUser['last_name'] ?? $request->input('last_name'),
            'username' => $telegramUser['username'] ?? $request->input('username'),
            'referred_by' => $request->input('referred_by'),
            'is_premium' => (bool) ($telegramUser['is_premium'] ?? false),
            'telegram_auth_date' => isset($payload['auth_date']) ? now()->setTimestamp((int) $payload['auth_date']) : null,
        ], [
            'telegram_id' => 'required',
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'username' => 'nullable|string',
            'referred_by' => 'sometimes|nullable',
            'is_premium' => 'boolean',
            'telegram_auth_date' => 'nullable|date',
        ])->validate();

        $levelId = (int) (Level::query()->min('id') ?: 1);
        $startingBalance = (int) config('clicker.economy.starting_balance', 1000);

        $defaults = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'username' => $validated['username'] ?? null,
            'referred_by' => null,
            'created_ip' => $ip,
            'is_premium' => $validated['is_premium'],
            'telegram_auth_date' => $validated['telegram_auth_date'],
            'balance' => $startingBalance,
            'earn_per_tap' => 1,
            'available_energy' => 500,
            'max_energy' => 500,
            'multi_tap_level' => 1,
            'energy_limit_level' => 1,
            'login_streak' => 0,
            'daily_booster_uses' => 0,
            'production_per_hour' => 0,
            'level_id' => $levelId,
            'last_tap_date' => now(),
            'last_login_date' => now(),
            'suspicious_score' => 0,
            'is_suspicious' => false,
        ];

        $user = TelegramUser::firstOrCreate(
            ['telegram_id' => $validated['telegram_id']],
            $defaults
        );

        if ($user->wasRecentlyCreated) {
            $accountsFromIp = TelegramUser::where('created_ip', $ip)->count();
            $suspiciousScore = 0;

            if ($accountsFromIp > $maxAccountsPerIp) {
                $user->delete();

                return response()->json([
                    'message' => 'Too many accounts from this device',
                ], 403);
            }

            if ($accountsFromIp > $suspiciousThreshold) {
                $suspiciousScore += 1;
            }

            if (($validated['referred_by'] ?? null) && $validated['referred_by'] != $validated['telegram_id']) {
                $referrer = TelegramUser::where('telegram_id', $validated['referred_by'])->first();

                if ($referrer && (string) $referrer->telegram_id !== (string) $user->telegram_id) {
                    $ipReferralCount = TelegramUser::where('created_ip', $ip)
                        ->where('referred_by', $referrer->telegram_id)
                        ->count();

                    if ($referrer->created_ip && $referrer->created_ip === $ip) {
                        $suspiciousScore += 3;
                    }

                    if ($ipReferralCount <= $maxReferralsPerIpPerReferrer) {
                        $user->referred_by = $referrer->telegram_id;
                    } else {
                        $suspiciousScore += 2;
                    }

                    if ($user->referred_by) {
                        $rewardConfig = $user->is_premium
                            ? config('clicker.referral.premium', [])
                            : config('clicker.referral.base', []);

                        $welcomeReward = (int) ($rewardConfig['welcome'] ?? 0);

                        if ($welcomeReward > 0) {
                            $this->walletService->credit($referrer, $welcomeReward, 'referral_welcome', [
                                'from_telegram_user_id' => $user->id,
                                'from_telegram_id' => $user->telegram_id,
                                'ip' => $ip,
                            ]);
                        }
                    }
                }
            }

            $user->suspicious_score = $suspiciousScore;
            $user->is_suspicious = $suspiciousScore > 0;
            $user->save();
        }

        $user->fill([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'username' => $validated['username'] ?? null,
            'is_premium' => $validated['is_premium'],
            'telegram_auth_date' => $validated['telegram_auth_date'],
            'last_tap_date' => $user->last_tap_date ?? now(),
            'last_login_date' => $user->last_login_date ?? now(),
            'level_id' => $user->level_id ?: $levelId,
            'earn_per_tap' => $user->earn_per_tap ?: 1,
            'available_energy' => $user->available_energy ?: 500,
            'max_energy' => $user->max_energy ?: 500,
        ])->save();

        $user->updateLoginStreak();
        $user->tokens()->delete();
        $token = $user->createToken((string) $user->telegram_id);

        return response()->json([
            'first_login' => $user->wasRecentlyCreated,
            'token' => $token->plainTextToken,
            'telegram_verified' => $isVerified,
            'auth_mode' => $isVerified ? 'telegram' : 'local_insecure',
        ]);
    }
}
