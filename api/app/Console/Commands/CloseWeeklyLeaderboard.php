<?php

namespace App\Console\Commands;

use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Models\WeeklyLeaderboardCycle;
use App\Models\WeeklyRewardLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseWeeklyLeaderboard extends Command
{
    protected $signature = 'weekly:close-leaderboard';
    protected $description = 'Close the current weekly leaderboard, reward top 3 with diamonds, and open the next cycle';

    public function handle(): int
    {
        $timezone = 'Asia/Almaty';
        $now = Carbon::now($timezone);

        $currentCycle = WeeklyLeaderboardCycle::query()
            ->where('is_closed', false)
            ->orderByDesc('id')
            ->first();

        if (!$currentCycle) {
            $start = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $end = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

            WeeklyLeaderboardCycle::create([
                'week_key' => $start->format('o-\WW'),
                'starts_at' => $start,
                'ends_at' => $end,
                'is_closed' => false,
            ]);

            $this->info('No active cycle found. New cycle created.');
            return self::SUCCESS;
        }

        if ($currentCycle->is_closed) {
            $this->info('Current cycle is already closed.');
            return self::SUCCESS;
        }

        if ($now->lt($currentCycle->ends_at)) {
            $this->info('Current cycle has not ended yet.');
            return self::SUCCESS;
        }

        $eligibleTypes = ['tap', 'earn', 'passive', 'referral'];
        $rewards = [
            1 => 100,
            2 => 50,
            3 => 25,
        ];

        $leaders = Transaction::query()
            ->selectRaw('telegram_user_id, SUM(amount) as weekly_score')
            ->whereIn('type', $eligibleTypes)
            ->where('amount', '>', 0)
            ->whereBetween('created_at', [$currentCycle->starts_at, $currentCycle->ends_at])
            ->groupBy('telegram_user_id')
            ->orderByDesc('weekly_score')
            ->limit(3)
            ->get()
            ->values();

        DB::transaction(function () use ($leaders, $rewards, $currentCycle, $now, $timezone) {
            foreach ($leaders as $index => $leader) {
                $rank = $index + 1;
                $diamonds = $rewards[$rank] ?? 0;

                if ($diamonds <= 0) {
                    continue;
                }

                $user = TelegramUser::query()
                    ->where('id', $leader->telegram_user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$user) {
                    continue;
                }

                $alreadyRewarded = WeeklyRewardLog::query()
                    ->where('telegram_user_id', $user->id)
                    ->where('weekly_leaderboard_cycle_id', $currentCycle->id)
                    ->exists();

                if ($alreadyRewarded) {
                    continue;
                }

                $user->diamonds_balance = (int) $user->diamonds_balance + $diamonds;
                $user->save();

                WeeklyRewardLog::create([
                    'telegram_user_id' => $user->id,
                    'weekly_leaderboard_cycle_id' => $currentCycle->id,
                    'rank' => $rank,
                    'diamonds_reward' => $diamonds,
                    'score' => (int) $leader->weekly_score,
                    'meta' => [
                        'reason' => 'weekly_leaderboard_reward',
                        'timezone' => $timezone,
                    ],
                ]);
            }

            $currentCycle->is_closed = true;
            $currentCycle->save();

            $nextStart = Carbon::parse($currentCycle->ends_at, $timezone)->addSecond()->startOfDay();
            $nextStart = $nextStart->startOfWeek(Carbon::MONDAY)->startOfDay();
            $nextEnd = $nextStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

            WeeklyLeaderboardCycle::firstOrCreate(
                ['week_key' => $nextStart->format('o-\WW')],
                [
                    'starts_at' => $nextStart,
                    'ends_at' => $nextEnd,
                    'is_closed' => false,
                ]
            );
        });

        $this->info('Weekly leaderboard closed and rewards distributed.');
        return self::SUCCESS;
    }
 }
