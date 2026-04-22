<?php

namespace App\Http\Controllers;

use App\Models\DailyTask;
use App\Models\Level;
use App\Models\TelegramUser;
use App\Models\WithdrawRequest;
use App\Services\WalletService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\WeeklyRewardLog;
use Carbon\Carbon;
class ClickerController extends Controller
{
    public function __construct(private WalletService $walletService)
    {
    }

    public function sync(Request $request)
    {
        /** @var TelegramUser|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $this->checkBoosterPackExpiration($user);
        $passiveEarnings = $user->calcPassiveEarning();
        $user->updateLoginStreak();
        $user->checkAndResetDailyBooster();
        $restoredEnergy = $user->restoreEnergy();
        $user->load('level');

        $canUseDailyBooster = $user->canUseDailyBooster();
        $levels = Level::all();
        $user->update(['last_login_date' => now()]);

        return response()->json([
            'user' => $user,
            'restored_energy' => $restoredEnergy,
            'boosters' => [
                'multi_tap' => [
                    'level' => $user->multi_tap_level,
                    'cost' => $this->getBoosterCost($user, 'multi_tap'),
                    'increase_by' => 1,
                ],
                'energy_limit' => [
                    'level' => $user->energy_limit_level,
                    'cost' => $this->getBoosterCost($user, 'energy_limit'),
                    'increase_by' => 500,
                ],
            ],
            'daily_booster' => [
                'can_use' => $canUseDailyBooster,
                'uses_today' => $user->daily_booster_uses,
                'next_available_at' => $canUseDailyBooster ? now() : ($user->last_daily_booster_use ? $user->last_daily_booster_use->copy()->addHours((int) config('clicker.economy.daily_booster_cooldown_hours', 4)) : null),
            ],
            'booster_packs' => [
                'booster_pack_2x' => ['cost' => 4000, 'duration_days' => 30, 'multiplier' => 2],
                'booster_pack_3x' => ['cost' => 7500, 'duration_days' => 30, 'multiplier' => 3],
                'booster_pack_7x' => ['cost' => 25000, 'duration_days' => 30, 'multiplier' => 7],
            ],
            'booster_pack_2x_active' => (bool) $user->booster_pack_2x,
            'booster_pack_3x_active' => (bool) $user->booster_pack_3x,
            'booster_pack_7x_active' => (bool) $user->booster_pack_7x,
            'booster_pack_active_until' => $user->booster_pack_active_until,
            'total_daily_rewards' => DailyTask::sum('reward_coins'),
            'levels' => $levels,
            'max_level' => $levels->max('level'),
            'level_up' => config('clicker.level_up'),
            'referral' => config('clicker.referral'),
            'passive_earnings' => $passiveEarnings,
            'total_referals' => TelegramUser::where('referred_by', $user->telegram_id)->count(),
            'wallet_summary' => array_merge($this->walletService->summary($user), [
                'ton_wallet' => $user->ton_wallet,
                'pending_withdraw' => (int) $user->withdrawRequests()->where('status', 'pending')->sum('amount'),
            ]),
            'withdraw_requests' => $this->walletService->withdrawSummary($user, 10)['items'],
        ]);
    }

public function tap(Request $request)
{
    $validated = $request->validate([
        'count' => 'required|integer|min:1|max:100',
    ]);

    /** @var TelegramUser|null $user */
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated',
        ], 401);
    }

    $user->restoreEnergy();

    $requestedCount = (int) $validated['count'];
    $energyPerTap = max(1, (int) $user->earn_per_tap);

    $maxPossibleTaps = intdiv(
        max(0, (int) $user->available_energy),
        $energyPerTap
    );

    $actualCount = min($requestedCount, $maxPossibleTaps);

    if ($actualCount <= 0) {
        $user->refresh()->load('level');

        return response()->json([
            'success' => true,
            'earned' => 0,
            'counted_taps' => 0,
            'requested_taps' => $requestedCount,
            'balance' => (int) $user->balance,
            'available_energy' => (int) $user->available_energy,
            'level' => $user->level,
            'earn_per_tap' => (int) $user->earn_per_tap,
            'max_energy' => (int) $user->max_energy,
            'message' => 'Not enough energy.',
        ]);
    }

    $earned = $actualCount * (int) $user->earn_per_tap;
    $spentEnergy = $actualCount * $energyPerTap;

$this->walletService->credit(
    $user,
    $earned,
    'tap',
    [
        'count' => $actualCount
    ]
);

// энергияны бөлек азайтамыз
$user->available_energy = max(0, (int) $user->available_energy - $spentEnergy);
$user->save();

    $user->refresh()->load('level');

    return response()->json([
        'success' => true,
        'earned' => $earned,
        'counted_taps' => $actualCount,
        'requested_taps' => $requestedCount,
        'balance' => (int) $user->balance,
        'available_energy' => (int) $user->available_energy,
        'level' => $user->level,
        'earn_per_tap' => (int) $user->earn_per_tap,
        'max_energy' => (int) $user->max_energy,
    ]);
}

    public function buyBoosterPack(Request $request)
    {
        $request->validate([
            'booster_pack' => 'required|in:booster_pack_2x,booster_pack_3x,booster_pack_7x',
        ]);

        /** @var TelegramUser|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $boosterPack = $request->input('booster_pack');
        $cost = $this->getBoosterPackCost($boosterPack);

        if ($user->balance < $cost) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough coins to buy this booster pack.',
                'required_coins' => $cost,
                'current_balance' => (int) $user->balance,
            ], 400);
        }

        try {
            DB::transaction(function () use ($user, $boosterPack, $cost) {
                $currentTime = new DateTime();
                $boosterActiveUntil = $user->booster_pack_active_until ? new DateTime($user->booster_pack_active_until) : null;

                if ($boosterActiveUntil && $boosterActiveUntil > $currentTime) {
                    if ($this->isValidUpgrade($user, $boosterPack)) {
                        $this->deactivateCurrentBooster($user);
                    } else {
                        throw new \InvalidArgumentException('Cannot downgrade or repurchase the same booster pack while one is active.');
                    }
                }

                $this->walletService->debit($user, $cost, 'booster_pack', [
                    'booster_pack' => $boosterPack,
                    'duration_days' => 30,
                ]);

                $this->activateBoosterPack($user, $boosterPack);
                $user->save();
            });

            $freshUser = $user->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Booster pack purchased successfully',
                'booster_pack_active_until' => $freshUser?->booster_pack_active_until,
                'balance' => $freshUser?->balance,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while purchasing the booster pack.',
            ], 500);
        }
    }

    private function isValidUpgrade($user, $newPack)
    {
        $packValues = ['booster_pack_2x' => 2, 'booster_pack_3x' => 3, 'booster_pack_7x' => 7];
        $currentPack = $this->getCurrentBoosterPack($user);

        return !$currentPack || $packValues[$newPack] > $packValues[$currentPack];
    }

    private function getCurrentBoosterPack($user)
    {
        if ($user->booster_pack_7x) return 'booster_pack_7x';
        if ($user->booster_pack_3x) return 'booster_pack_3x';
        if ($user->booster_pack_2x) return 'booster_pack_2x';
        return null;
    }


    private function getBoosterPackCost(string $boosterPack): int
    {
        return match ($boosterPack) {
            'booster_pack_2x' => 4000,
            'booster_pack_3x' => 7500,
            'booster_pack_7x' => 25000,
            default => throw new \InvalidArgumentException('Invalid booster pack type.'),
        };
    }

    private function deactivateCurrentBooster($user)
    {
        $user->booster_pack_2x = 0;
        $user->booster_pack_3x = 0;
        $user->booster_pack_7x = 0;
    }

    private function activateBoosterPack($user, $boosterPack)
    {
        match ($boosterPack) {
            'booster_pack_2x' => $user->booster_pack_2x = 1,
            'booster_pack_3x' => $user->booster_pack_3x = 1,
            'booster_pack_7x' => $user->booster_pack_7x = 1,
            default => throw new \InvalidArgumentException("Invalid booster pack type: {$boosterPack}"),
        };

        $user->booster_pack_active_until = now()->addDays(30)->format('Y-m-d H:i:s');
    }

    private function checkBoosterPackExpiration($user)
    {
        if (!$user->booster_pack_active_until) {
            return;
        }

        $expirationDate = new DateTime($user->booster_pack_active_until);
        if (new DateTime() > $expirationDate) {
            $user->booster_pack_2x = 0;
            $user->booster_pack_3x = 0;
            $user->booster_pack_7x = 0;
            $user->booster_pack_active_until = null;
            $user->save();
        }
    }

    public function buyBooster(Request $request)
    {
        $request->validate([
            'booster_type' => 'required|in:multi_tap,energy_limit',
        ]);

        /** @var TelegramUser|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $boosterType = $request->input('booster_type');
        $cost = $this->getBoosterCost($user, $boosterType);

        if ($user->balance < $cost) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough coins to buy this booster.',
                'required_coins' => $cost,
                'current_balance' => $user->balance,
            ], 400);
        }

        try {
            DB::transaction(function () use ($user, $boosterType, $cost) {
                $this->walletService->debit($user, $cost, 'boost', ['booster_type' => $boosterType]);

                switch ($boosterType) {
                    case 'multi_tap':
                        $user->multi_tap_level++;
                        $user->earn_per_tap++;
                        break;
                    case 'energy_limit':
                        $user->energy_limit_level++;
                        $user->max_energy += 500;
                        break;
                }

                $user->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Booster purchased successfully',
                'balance' => $user->balance,
                'earn_per_tap' => $user->earn_per_tap,
                'max_energy' => $user->max_energy,
                'multi_tap_level' => $user->multi_tap_level,
                'energy_limit_level' => $user->energy_limit_level,
                'next_multi_tap_cost' => $this->getBoosterCost($user, 'multi_tap'),
                'next_energy_limit_cost' => $this->getBoosterCost($user, 'energy_limit'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while purchasing the booster.',
            ], 500);
        }
    }

private function getBoosterCost($user, $boosterType)
{
    $priceMap = [
        'multi_tap' => [
            1 => 1000,
            2 => 2000,
            3 => 6000,
            4 => 8000,
            5 => 12000,
            6 => 16000,
            7 => 24000,
            8 => 32000,
            9 => 50000,
            10 => 75000,
        ],
        'energy_limit' => [
            1 => 1000,
            2 => 2000,
            3 => 6000,
            4 => 8000,
            5 => 12000,
            6 => 16000,
            7 => 24000,
            8 => 32000,
            9 => 50000,
            10 => 75000,
        ],
    ];

    $boostLevel = $boosterType === 'multi_tap'
        ? (int) $user->multi_tap_level
        : (int) $user->energy_limit_level;

    return $priceMap[$boosterType][$boostLevel] ?? 100000;
}
    public function listDailyTasks(Request $request)
    {
        /** @var TelegramUser|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $dailyTasks = DailyTask::query()
            ->leftJoin('telegram_user_daily_tasks', function ($join) use ($user) {
                $join->on('daily_tasks.id', '=', 'telegram_user_daily_tasks.daily_task_id')
                    ->where('telegram_user_daily_tasks.telegram_user_id', $user->id);
            })
            ->select(['daily_tasks.*', 'telegram_user_daily_tasks.completed'])
            ->selectRaw('daily_tasks.required_login_streak <= ? as available', [$user->login_streak])
            ->get();

        return response()->json($dailyTasks);
    }

   public function listLeaderboard(Request $request)
{
    /** @var TelegramUser|null $user */
    $user = $request->user();

    $topUsers = TelegramUser::query()
        ->select(['id', 'telegram_id', 'first_name', 'last_name', 'username', 'balance', 'production_per_hour', 'level_id'])
        ->orderByDesc('balance')
        ->limit(50)
        ->get()
        ->map(function (TelegramUser $player) {
            return [
                'id' => $player->id,
                'telegram_id' => $player->telegram_id,
                'display_name' => trim(($player->first_name ?? '') . ' ' . ($player->last_name ?? '')) ?: ($player->username ?: 'Player'),
                'first_name' => $player->first_name,
                'last_name' => $player->last_name,
                'username' => $player->username,
                'balance' => $player->balance,
                'production_per_hour' => $player->production_per_hour,
                'level_id' => $player->level_id,
            ];
        })
        ->values();

    $myRank = null;
    $me = null;

    if ($user) {
        $myBalance = (int) $user->balance;
        $myRank = TelegramUser::where('balance', '>', $myBalance)->count() + 1;

        $me = [
            'id' => $user->id,
            'display_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?: 'Player'),
            'telegram_id' => $user->telegram_id,
            'balance' => $user->balance,
            'production_per_hour' => $user->production_per_hour,
            'level_id' => $user->level_id,
            'rank' => $myRank,
        ];
    }

    return response()->json([
        'items' => $topUsers,
        'me' => $me,
        'my_rank' => $myRank,
    ]);
}

    public function useDailyBooster(Request $request)
    {
        /** @var TelegramUser|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->useDailyBooster()) {
            return response()->json([
                'success' => true,
                'message' => 'Daily booster used successfully',
                'current_energy' => $user->max_energy,
                'daily_booster_uses' => $user->daily_booster_uses,
                'next_available_at' => $user->last_daily_booster_use?->copy()->addHours((int) config('clicker.economy.daily_booster_cooldown_hours', 4)),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cannot use daily booster at this time',
            'daily_booster_uses' => $user->daily_booster_uses,
            'next_available_at' => $user->last_daily_booster_use ? $user->last_daily_booster_use->copy()->addHours((int) config('clicker.economy.daily_booster_cooldown_hours', 4)) : null,
        ], 400);
    }

    public function claimDailyTaskReward(Request $request)
    {
        /** @var TelegramUser|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $task = DailyTask::where('required_login_streak', '<=', $user->login_streak)
            ->whereDoesntHave('telegramUsers', function ($query) use ($user) {
                $query->where('telegram_user_id', $user->id);
            })
            ->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to claim daily task reward. Task may not be available or already completed for today.',
            ], 400);
        }

        DB::transaction(function () use ($task, $user) {
            $this->walletService->credit($user, $task->reward_coins, 'earn', ['daily_task_id' => $task->id, 'title' => $task->name]);
            $user->dailyTasks()->attach($task->id, ['completed' => true, 'updated_at' => now()]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Daily task reward claimed successfully',
            'balance' => $user->balance,
        ]);
    }

    public function setTonWallet(Request $request)
    {
        $request->validate([
            'ton_wallet' => ['required', 'string', 'min:24', 'max:255', 'regex:/^(UQ|EQ|kQ|0Q)[A-Za-z0-9_\-]{40,120}$/'],
            'wallet_provider' => ['nullable', 'string', 'max:80'],
        ]);

        /** @var TelegramUser|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $user->ton_wallet = $request->input('ton_wallet');
        $user->wallet_provider = $request->input('wallet_provider', 'manual');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'TON Wallet address updated successfully',
            'ton_wallet' => $user->ton_wallet,
            'wallet_provider' => $user->wallet_provider,
        ]);
    }

    public function walletSummary(Request $request)
    {
        /** @var TelegramUser|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'summary' => $this->walletService->summary($user),
        ]);
    }

    public function transactions(Request $request)
    {
        /** @var TelegramUser|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'items' => $this->walletService->transactionHistory($user, 50),
        ]);
    }

    public function withdrawRequests(Request $request)
    {
        /** @var TelegramUser|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json($this->walletService->withdrawSummary($user, 20));
    }

    public function createWithdrawRequest(Request $request)
{
    $validated = $request->validate([
        'amount' => 'required|integer|min:'.config('clicker.economy.withdraw.min_amount', 10000),
        'wallet_address' => ['required', 'string', 'min:24', 'max:255', 'regex:/^(UQ|EQ|kQ|0Q)[A-Za-z0-9_\-]{40,120}$/'],
        'network' => ['nullable', 'string', 'max:32'],
        'client_request_id' => ['nullable', 'string', 'max:120'],
    ]);

    $user = $request->user();
    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $amount = (int) $validated['amount'];
    $clientRequestId = $validated['client_request_id'] ?? null;

    try {
        $duplicateRequest = null;

        DB::transaction(function () use ($user, $validated, $amount, $clientRequestId, &$duplicateRequest) {
            $user = TelegramUser::where('id', $user->id)->lockForUpdate()->first();

            if ($clientRequestId) {
                $duplicateRequest = WithdrawRequest::query()
                    ->where('telegram_user_id', $user->id)
                    ->where('client_request_id', $clientRequestId)
                    ->first();

                if ($duplicateRequest) {
                    return;
                }
            }

            $hasPending = WithdrawRequest::query()
                ->where('telegram_user_id', $user->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();

            if ($hasPending) {
                throw new \InvalidArgumentException('You already have a pending withdraw request.');
            }

            if ($amount > (int) $user->balance) {
                throw new \InvalidArgumentException('Insufficient balance');
            }

            $this->walletService->debit($user, $amount, 'withdraw', [
                'wallet_address' => $validated['wallet_address'],
                'network' => $validated['network'] ?? 'TON',
                'stage' => 'request_created',
                'client_request_id' => $clientRequestId,
            ]);

            $user->ton_wallet = $validated['wallet_address'];
            $user->save();

            WithdrawRequest::create([
                'telegram_user_id' => $user->id,
                'amount' => $amount,
                'wallet_address' => $validated['wallet_address'],
                'network' => $validated['network'] ?? 'TON',
                'status' => 'pending',
                'client_request_id' => $clientRequestId,
                'verification_status' => 'pending',
            ]);
        });

        if ($duplicateRequest) {
            return response()->json([
                'success' => true,
                'message' => 'Withdraw request already exists.',
                'balance' => (int) $user->fresh()->balance,
                'pending_total' => (int) $this->walletService->withdrawSummary($user->fresh(), 20)['pending_total'],
            ]);
        }

    } catch (\InvalidArgumentException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Withdraw failed. Try again.',
        ], 500);
    }

    $freshUser = $user->fresh();
    $withdrawSummary = $this->walletService->withdrawSummary($freshUser, 20);

    return response()->json([
        'success' => true,
        'message' => 'Withdraw request created successfully.',
        'balance' => $freshUser->balance,
        'pending_total' => (int) $withdrawSummary['pending_total'],
    ]);
}
public function weeklyLeaderboard(Request $request)
{
    /** @var TelegramUser|null $user */
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $timezone = 'Asia/Almaty';
    $now = Carbon::now($timezone);

    $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
    $endOfWeek = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

    $eligibleTypes = ['tap', 'earn', 'passive', 'referral'];

    $rows = Transaction::query()
        ->selectRaw('telegram_user_id, SUM(amount) as weekly_score')
        ->whereIn('type', $eligibleTypes)
        ->where('amount', '>', 0)
        ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
        ->groupBy('telegram_user_id')
        ->orderByDesc('weekly_score')
        ->limit(50)
        ->get();

    $userIds = $rows->pluck('telegram_user_id')->all();
    $players = TelegramUser::whereIn('id', $userIds)->get()->keyBy('id');

    $items = $rows->values()->map(function ($row, $index) use ($players) {
        $player = $players[$row->telegram_user_id] ?? null;

        return [
            'id' => $player?->id,
            'telegram_id' => $player?->telegram_id,
            'display_name' => $player
                ? (trim(($player->first_name ?? '') . ' ' . ($player->last_name ?? '')) ?: ($player->username ?: 'Player'))
                : 'Player',
            'level_id' => $player?->level_id,
            'weekly_score' => (int) $row->weekly_score,
            'rank' => $index + 1,
        ];
    })->values();

    $myScore = (int) Transaction::query()
        ->where('telegram_user_id', $user->id)
        ->whereIn('type', $eligibleTypes)
        ->where('amount', '>', 0)
        ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
        ->sum('amount');

    $myRank = null;

    if ($myScore > 0) {
        $betterCount = Transaction::query()
            ->selectRaw('telegram_user_id, SUM(amount) as weekly_score')
            ->whereIn('type', $eligibleTypes)
            ->where('amount', '>', 0)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupBy('telegram_user_id')
            ->havingRaw('SUM(amount) > ?', [$myScore])
            ->get()
            ->count();

        $myRank = $betterCount + 1;
    }

    $lastReward = WeeklyRewardLog::query()
        ->where('telegram_user_id', $user->id)
        ->latest()
        ->first();

    return response()->json([
        'week' => [
            'starts_at' => $startOfWeek,
            'ends_at' => $endOfWeek,
            'timezone' => $timezone,
        ],
        'rewards' => [
            ['rank' => 1, 'diamonds' => 100],
            ['rank' => 2, 'diamonds' => 50],
            ['rank' => 3, 'diamonds' => 25],
        ],
        'items' => $items,
        'me' => [
            'id' => $user->id,
            'display_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?: 'Player'),
            'level_id' => $user->level_id,
            'weekly_score' => $myScore,
            'diamonds_balance' => (int) $user->diamonds_balance,
        ],
        'my_rank' => $myRank,
        'last_week_reward' => $lastReward ? [
            'rank' => $lastReward->rank,
            'diamonds_reward' => $lastReward->diamonds_reward,
            'created_at' => $lastReward->created_at,
        ] : null,
    ]);
}
}
