<?php

namespace App\Http\Controllers;

use App\Models\BattleQueue;
use App\Models\BattleRoom;
use App\Models\TelegramUser;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BattleController extends Controller
{
    private const QUEUE_TIMEOUT_SECONDS = 8;
    private const COUNTDOWN_SECONDS = 3;
    private const BATTLE_DURATION_SECONDS = 15;
    private const MAX_HIT_RATE_PER_SECOND = 5;
    private const MIN_MILLISECONDS_BETWEEN_HITS = 120;

    private const ROOM_TIERS = [
        'bronze' => 500,
        'silver' => 1000,
        'gold' => 5000,
    ];

    public function __construct(private WalletService $walletService)
    {
    }

    public function current(Request $request)
    {
        /** @var TelegramUser $user */
        $user = $request->user();

        $room = BattleRoom::query()
            ->where(function ($query) use ($user) {
                $query->where('telegram_user_id', $user->id)
                    ->orWhere('player_one_id', $user->id)
                    ->orWhere('player_two_id', $user->id);
            })
            ->whereIn('status', ['matched', 'countdown', 'active'])
            ->latest('id')
            ->first();

        if ($room) {
            $room = $this->syncRoomState($room);

            return response()->json([
                'success' => true,
                'room' => $this->serializeRoom($room, $user),
                'user' => $this->userState($user->fresh()),
            ]);
        }

        $queue = BattleQueue::query()
            ->where('telegram_user_id', $user->id)
            ->where('status', 'waiting')
            ->latest('id')
            ->first();

        if ($queue) {
            if ($queue->expires_at && now()->greaterThanOrEqualTo($queue->expires_at)) {
                $queue->update(['status' => 'expired']);
                $room = $this->createBotRoom($user, (string) $queue->room_tier, (int) $queue->stake_amount, $queue->created_at);

                return response()->json([
                    'success' => true,
                    'room' => $this->serializeRoom($room, $user),
                    'user' => $this->userState($user->fresh()),
                ]);
            }

            return response()->json([
                'success' => true,
                'searching' => true,
                'queue' => [
                    'room_tier' => $queue->room_tier,
                    'stake_amount' => (int) $queue->stake_amount,
                ],
                'expires_at' => optional($queue->expires_at)?->toIso8601String(),
                'user' => $this->userState($user->fresh()),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No active battle.',
        ], 404);
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'room_tier' => 'nullable|string|in:bronze,silver,gold',
        ]);

        /** @var TelegramUser $user */
        $user = $request->user();
        $tier = (string) ($validated['room_tier'] ?? 'silver');
        $stake = $this->stakeForTier($tier);

        if ((float) $user->balance < $stake) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough balance for this arena.',
                'required' => $stake,
                'balance' => (float) $user->balance,
            ], 422);
        }

        $existingRoom = BattleRoom::query()
            ->where(function ($query) use ($user) {
                $query->where('telegram_user_id', $user->id)
                    ->orWhere('player_one_id', $user->id)
                    ->orWhere('player_two_id', $user->id);
            })
            ->whereIn('status', ['matched', 'countdown', 'active'])
            ->latest('id')
            ->first();

        if ($existingRoom) {
            $existingRoom = $this->syncRoomState($existingRoom);

            return response()->json([
                'success' => true,
                'room' => $this->serializeRoom($existingRoom, $user),
                'user' => $this->userState($user->fresh()),
            ]);
        }

        $waitingQueue = BattleQueue::query()
            ->where('telegram_user_id', $user->id)
            ->where('status', 'waiting')
            ->latest('id')
            ->first();

        if ($waitingQueue) {
            if ($waitingQueue->expires_at && now()->greaterThanOrEqualTo($waitingQueue->expires_at)) {
                $waitingQueue->update(['status' => 'expired']);
                $room = $this->createBotRoom($user, (string) $waitingQueue->room_tier, (int) $waitingQueue->stake_amount, $waitingQueue->created_at);

                return response()->json([
                    'success' => true,
                    'room' => $this->serializeRoom($room, $user),
                    'user' => $this->userState($user->fresh()),
                ]);
            }

            if ($waitingQueue->room_tier !== $tier) {
                $waitingQueue->update([
                    'room_tier' => $tier,
                    'stake_amount' => $stake,
                    'expires_at' => now()->addSeconds(self::QUEUE_TIMEOUT_SECONDS),
                ]);
            }

            return response()->json([
                'success' => true,
                'searching' => true,
                'queue' => [
                    'room_tier' => $tier,
                    'stake_amount' => $stake,
                ],
                'expires_at' => optional($waitingQueue->fresh()->expires_at)?->toIso8601String(),
                'user' => $this->userState($user->fresh()),
            ]);
        }

        $matchedRoom = DB::transaction(function () use ($user, $tier, $stake) {
            $opponentQueue = BattleQueue::query()
                ->where('status', 'waiting')
                ->where('telegram_user_id', '!=', $user->id)
                ->where('room_tier', $tier)
                ->where('stake_amount', $stake)
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->lockForUpdate()
                ->oldest('id')
                ->first();

            if (!$opponentQueue) {
                BattleQueue::query()
                    ->where('telegram_user_id', $user->id)
                    ->whereIn('status', ['waiting', 'matched', 'expired', 'cancelled'])
                    ->delete();

                BattleQueue::create([
                    'telegram_user_id' => $user->id,
                    'room_tier' => $tier,
                    'stake_amount' => $stake,
                    'status' => 'waiting',
                    'expires_at' => now()->addSeconds(self::QUEUE_TIMEOUT_SECONDS),
                ]);

                return null;
            }

            $playerOne = TelegramUser::query()->lockForUpdate()->find($user->id);
            $playerTwo = TelegramUser::query()->lockForUpdate()->find($opponentQueue->telegram_user_id);

            if (!$playerOne || !$playerTwo) {
                $opponentQueue->delete();
                return null;
            }

            if ((float) $playerOne->balance < $stake || (float) $playerTwo->balance < $stake) {
                $opponentQueue->update(['status' => 'cancelled']);
                return null;
            }

            $this->walletService->debit($playerOne, $stake, 'battle_entry', [
                'tier' => $tier,
                'mode' => 'pvp',
            ]);
            $this->walletService->debit($playerTwo, $stake, 'battle_entry', [
                'tier' => $tier,
                'mode' => 'pvp',
            ]);

            $opponentQueue->update(['status' => 'matched']);
            BattleQueue::query()
                ->where('telegram_user_id', $user->id)
                ->where('status', 'waiting')
                ->delete();

            return BattleRoom::create([
                'telegram_user_id' => $user->id,
                'player_one_id' => $playerOne->id,
                'player_two_id' => $playerTwo->id,
                'status' => 'countdown',
                'mode' => 'pvp',
                'room_tier' => $tier,
                'stake_amount' => $stake,
                'opponent_name' => $playerTwo->first_name ?: 'Player',
                'player_score' => 0,
                'bot_score' => 0,
                'player_one_score' => 0,
                'player_two_score' => 0,
                'duration_seconds' => self::BATTLE_DURATION_SECONDS,
                'reward' => 0,
                'support_spent' => 0,
                'player_one_support_spent' => 0,
                'player_two_support_spent' => 0,
                'search_started_at' => now(),
                'countdown_started_at' => now(),
                'started_at' => now()->addSeconds(self::COUNTDOWN_SECONDS),
                'ends_at' => now()->addSeconds(self::COUNTDOWN_SECONDS + self::BATTLE_DURATION_SECONDS),
                'player_one_ready_at' => now(),
                'player_two_ready_at' => now(),
            ]);
        });

        if (!$matchedRoom) {
            return response()->json([
                'success' => true,
                'searching' => true,
                'queue' => [
                    'room_tier' => $tier,
                    'stake_amount' => $stake,
                ],
                'expires_at' => now()->addSeconds(self::QUEUE_TIMEOUT_SECONDS)->toIso8601String(),
                'user' => $this->userState($user->fresh()),
            ]);
        }

        return response()->json([
            'success' => true,
            'matched' => true,
            'room' => $this->serializeRoom($matchedRoom, $user),
            'user' => $this->userState($user->fresh()),
        ]);
    }

    public function accept(Request $request)
    {
        /** @var TelegramUser $user */
        $user = $request->user();

        $room = BattleRoom::query()
            ->whereIn('status', ['matched', 'countdown'])
            ->where(function ($query) use ($user) {
                $query->where('player_one_id', $user->id)
                    ->orWhere('player_two_id', $user->id);
            })
            ->latest('id')
            ->firstOrFail();

        if ((int) $room->player_one_id === (int) $user->id) {
            $room->player_one_ready_at = now();
        }

        if ((int) $room->player_two_id === (int) $user->id) {
            $room->player_two_ready_at = now();
        }

        if ($room->status === 'matched') {
            $room->status = 'countdown';
            $room->countdown_started_at = now();
            $room->started_at = now()->addSeconds(self::COUNTDOWN_SECONDS);
            $room->ends_at = now()->addSeconds(self::COUNTDOWN_SECONDS + self::BATTLE_DURATION_SECONDS);
        }

        $room->save();

        return response()->json([
            'success' => true,
            'room' => $this->serializeRoom($this->syncRoomState($room->fresh()), $user),
            'user' => $this->userState($user->fresh()),
        ]);
    }

    public function punch(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|integer',
        ]);

        /** @var TelegramUser $user */
        $user = $request->user();
        $room = $this->resolveRoomForUser($validated['room_id'], $user);
        $room = $this->syncRoomState($room);

        if ($room->status !== 'active') {
            return response()->json(['message' => 'Battle is not active.'], 422);
        }

        if ((int) $user->available_energy <= 0) {
            return response()->json(['message' => 'Not enough energy.'], 422);
        }

        $userHitKey = sprintf('battle-hit:%s:%s', $room->id, $user->id);
        $lastHitAt = (float) Cache::get($userHitKey.':last', 0);
        $nowMicro = microtime(true);

        if ($lastHitAt > 0 && (($nowMicro - $lastHitAt) * 1000) < self::MIN_MILLISECONDS_BETWEEN_HITS) {
            return response()->json(['message' => 'Punching too fast.'], 429);
        }

        $bucket = Cache::get($userHitKey.':bucket', ['window' => time(), 'count' => 0]);
        $window = (int) ($bucket['window'] ?? time());
        $count = (int) ($bucket['count'] ?? 0);

        if ($window !== time()) {
            $window = time();
            $count = 0;
        }

        if ($count >= self::MAX_HIT_RATE_PER_SECOND) {
            return response()->json(['message' => 'Hit rate exceeded.'], 429);
        }

        Cache::put($userHitKey.':last', $nowMicro, now()->addSeconds(30));
        Cache::put($userHitKey.':bucket', ['window' => $window, 'count' => $count + 1], now()->addSeconds(30));

        DB::transaction(function () use ($room, $user) {
            $room->refresh();
            $user->refresh();

            $scoreField = $this->scoreFieldForUser($room, $user);
            $legacyField = (int) $room->player_one_id === (int) $user->id ? 'player_score' : 'bot_score';
            $power = max(1, (int) $user->earn_per_tap);

            $user->available_energy = max(0, (int) $user->available_energy - 1);
            $user->last_tap_date = now();
            $user->save();

            $room->{$scoreField} = (int) $room->{$scoreField} + $power;
            $room->{$legacyField} = (int) $room->{$legacyField} + $power;
            $room->save();
        });

        return response()->json([
            'success' => true,
            'room' => $this->serializeRoom($this->syncRoomState($room->fresh()), $user->fresh()),
            'user' => $this->userState($user->fresh()),
        ]);
    }

    public function boost(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|integer',
            'amount' => 'required|integer|in:100,500,2000',
        ]);

        /** @var TelegramUser $user */
        $user = $request->user();
        $room = $this->resolveRoomForUser($validated['room_id'], $user);
        $room = $this->syncRoomState($room);

        if ($room->status !== 'active') {
            return response()->json(['message' => 'Battle is not active.'], 422);
        }

        try {
            $this->walletService->debit($user, $validated['amount'], 'battle_support', [
                'room_id' => $room->id,
                'kind' => 'support_boost',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $scoreField = $this->scoreFieldForUser($room, $user);
        $supportField = $this->supportFieldForUser($room, $user);
        $legacyField = (int) $room->player_one_id === (int) $user->id ? 'player_score' : 'bot_score';

        $room->{$scoreField} = (int) $room->{$scoreField} + $validated['amount'];
        $room->{$supportField} = (int) $room->{$supportField} + $validated['amount'];
        $room->{$legacyField} = (int) $room->{$legacyField} + $validated['amount'];
        $room->support_spent = (int) ($room->player_one_support_spent ?? 0) + (int) ($room->player_two_support_spent ?? 0);
        $room->save();

        return response()->json([
            'success' => true,
            'room' => $this->serializeRoom($this->syncRoomState($room->fresh()), $user->fresh()),
            'user' => $this->userState($user->fresh()),
        ]);
    }

    public function finish(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|integer',
        ]);

        /** @var TelegramUser $user */
        $user = $request->user();
        $room = $this->resolveRoomForUser($validated['room_id'], $user);
        $room = $this->syncRoomState($room);

        return response()->json([
            'success' => true,
            'room' => $this->serializeRoom($this->finishRoom($room), $user->fresh()),
            'user' => $this->userState($user->fresh()),
        ]);
    }

    private function resolveRoomForUser(int $roomId, TelegramUser $user): BattleRoom
    {
        return BattleRoom::query()
            ->where('id', $roomId)
            ->where(function ($query) use ($user) {
                $query->where('telegram_user_id', $user->id)
                    ->orWhere('player_one_id', $user->id)
                    ->orWhere('player_two_id', $user->id);
            })
            ->firstOrFail();
    }

    private function syncRoomState(BattleRoom $room): BattleRoom
    {
        if ($room->mode === 'bot') {
            $room = $this->syncBotRoom($room);
        }

        if ($room->status === 'countdown' && $room->started_at && now()->greaterThanOrEqualTo($room->started_at)) {
            $room->status = 'active';
            $room->save();
        }

        if ($room->status === 'active' && $room->ends_at && now()->greaterThanOrEqualTo($room->ends_at)) {
            $room = $this->finishRoom($room);
        }

        return $room->fresh();
    }

    private function finishRoom(BattleRoom $room): BattleRoom
    {
        $room->refresh();

        if ($room->status === 'finished') {
            return $room;
        }

        $room = DB::transaction(function () use ($room) {
            /** @var BattleRoom $locked */
            $locked = BattleRoom::query()->lockForUpdate()->findOrFail($room->id);
            if ($locked->status === 'finished') {
                return $locked;
            }

            $playerOne = TelegramUser::query()->lockForUpdate()->find($locked->player_one_id ?: $locked->telegram_user_id);
            $playerTwo = $locked->player_two_id ? TelegramUser::query()->lockForUpdate()->find($locked->player_two_id) : null;

            $playerOneScore = (int) ($locked->player_one_score ?: $locked->player_score ?: 0);
            $playerTwoScore = (int) ($locked->player_two_score ?: $locked->bot_score ?: 0);

            $locked->player_score = $playerOneScore;
            $locked->bot_score = $playerTwoScore;
            $locked->finished_at = now();
            $locked->status = 'finished';

            if ($playerOneScore > $playerTwoScore) {
                $locked->winner_id = $playerOne?->id;
                $locked->result_type = 'win';
            } elseif ($playerTwoScore > $playerOneScore) {
                $locked->winner_id = $playerTwo?->id;
                $locked->result_type = 'lose';
            } else {
                $locked->winner_id = null;
                $locked->result_type = 'draw';
            }

            if ($locked->mode === 'pvp' && $playerOne && $playerTwo) {
                $stake = max(0, (int) $locked->stake_amount);
                $winnerReward = (int) floor($stake * 1.80);
                $loserReward = (int) floor($stake * 0.05);
                $feeAmount = max(0, (int) (($stake * 2) - $winnerReward - $loserReward));

                $locked->winner_reward = $winnerReward;
                $locked->loser_reward = $loserReward;
                $locked->fee_amount = $feeAmount;

                if ($locked->result_type === 'draw') {
                    $locked->winner_reward = $stake;
                    $locked->loser_reward = $stake;
                    $locked->fee_amount = 0;
                    $locked->reward = $stake;

                    $this->walletService->credit($playerOne, $stake, 'battle_refund', [
                        'room_id' => $locked->id,
                        'result' => 'draw',
                        'tier' => $locked->room_tier,
                    ]);
                    $this->walletService->credit($playerTwo, $stake, 'battle_refund', [
                        'room_id' => $locked->id,
                        'result' => 'draw',
                        'tier' => $locked->room_tier,
                    ]);
                } else {
                    $winner = (int) $locked->winner_id === (int) $playerOne->id ? $playerOne : $playerTwo;
                    $loser = (int) $locked->winner_id === (int) $playerOne->id ? $playerTwo : $playerOne;
                    $locked->reward = $winnerReward;

                    $this->walletService->credit($winner, $winnerReward, 'battle_reward', [
                        'room_id' => $locked->id,
                        'result' => 'win',
                        'tier' => $locked->room_tier,
                        'mode' => 'pvp',
                    ]);

                    if ($loserReward > 0) {
                        $this->walletService->credit($loser, $loserReward, 'battle_consolation', [
                            'room_id' => $locked->id,
                            'result' => 'lose',
                            'tier' => $locked->room_tier,
                            'mode' => 'pvp',
                        ]);
                    }
                }
            } else {
                $stake = max(0, (int) $locked->stake_amount);
                $botWinReward = (int) floor($stake * 1.60);
                $drawReward = $stake;

                if ($locked->result_type === 'win' && $playerOne) {
                    $locked->reward = $botWinReward;
                    $locked->winner_reward = $botWinReward;
                    $locked->loser_reward = 0;
                    $locked->fee_amount = max(0, $stake - $botWinReward);
                    $this->walletService->credit($playerOne, $botWinReward, 'battle_reward', [
                        'room_id' => $locked->id,
                        'result' => 'win',
                        'tier' => $locked->room_tier,
                        'mode' => 'bot',
                    ]);
                } elseif ($locked->result_type === 'draw' && $playerOne) {
                    $locked->reward = $drawReward;
                    $locked->winner_reward = $drawReward;
                    $locked->loser_reward = 0;
                    $locked->fee_amount = 0;
                    $this->walletService->credit($playerOne, $drawReward, 'battle_refund', [
                        'room_id' => $locked->id,
                        'result' => 'draw',
                        'tier' => $locked->room_tier,
                        'mode' => 'bot',
                    ]);
                } else {
                    $locked->reward = 0;
                    $locked->winner_reward = 0;
                    $locked->loser_reward = 0;
                    $locked->fee_amount = $stake;
                }
            }

            $locked->save();
            BattleQueue::query()
                ->whereIn('telegram_user_id', array_filter([$locked->player_one_id, $locked->player_two_id]))
                ->delete();

            return $locked;
        });

        return $room->fresh();
    }

    private function createBotRoom(TelegramUser $user, string $tier, int $stake, $searchStartedAt = null): BattleRoom
    {
        if ((float) $user->balance < $stake) {
            throw new InvalidArgumentException('Insufficient balance.');
        }

        $this->walletService->debit($user, $stake, 'battle_entry', [
            'tier' => $tier,
            'mode' => 'bot',
        ]);

        BattleQueue::query()
            ->where('telegram_user_id', $user->id)
            ->delete();

        return BattleRoom::create([
            'telegram_user_id' => $user->id,
            'player_one_id' => $user->id,
            'player_two_id' => null,
            'status' => 'countdown',
            'mode' => 'bot',
            'room_tier' => $tier,
            'stake_amount' => $stake,
            'bot_profile' => collect(['easy', 'medium', 'hard'])->random(),
            'search_started_at' => $searchStartedAt ?: now(),
            'countdown_started_at' => now(),
            'opponent_name' => collect(['Iron Kong', 'Red Fang', 'Shadow Ape', 'Rage Monkey'])->random(),
            'player_score' => 0,
            'bot_score' => 0,
            'player_one_score' => 0,
            'player_two_score' => 0,
            'duration_seconds' => self::BATTLE_DURATION_SECONDS,
            'started_at' => now()->addSeconds(self::COUNTDOWN_SECONDS),
            'ends_at' => now()->addSeconds(self::COUNTDOWN_SECONDS + self::BATTLE_DURATION_SECONDS),
            'finished_at' => null,
            'result' => null,
            'result_type' => null,
            'reward' => 0,
            'winner_reward' => 0,
            'loser_reward' => 0,
            'fee_amount' => 0,
            'support_spent' => 0,
            'player_one_support_spent' => 0,
            'player_two_support_spent' => 0,
            'player_one_ready_at' => now(),
        ]);
    }

    private function syncBotRoom(BattleRoom $room): BattleRoom
    {
        if ($room->mode !== 'bot') {
            return $room;
        }

        $playerOneScore = (int) ($room->player_one_score ?: $room->player_score ?: 0);
        $room->player_one_score = $playerOneScore;
        $room->player_score = $playerOneScore;

        if ($room->status === 'active' && $room->started_at) {
            $elapsed = max(0, min(now()->timestamp - $room->started_at->timestamp, (int) $room->duration_seconds));
            $basePerSecond = match ($room->bot_profile) {
                'easy' => 5,
                'hard' => 9,
                default => 7,
            };

            $burst = 0;
            if ($elapsed > 5) {
                $burst += intdiv($elapsed, 5) * 2;
            }
            if ($elapsed > ((int) $room->duration_seconds - 4)) {
                $burst += 12;
            }

            $botScore = max(0, ($elapsed * $basePerSecond) + $burst);
            $room->player_two_score = $botScore;
            $room->bot_score = $botScore;
            $room->save();
        }

        return $room->fresh();
    }

    private function serializeRoom(BattleRoom $room, TelegramUser $viewer): array
    {
        $isPlayerOne = (int) $room->player_one_id === (int) $viewer->id || (!$room->player_one_id && (int) $room->telegram_user_id === (int) $viewer->id);

        $myScore = $isPlayerOne ? (int) ($room->player_one_score ?: $room->player_score ?: 0) : (int) ($room->player_two_score ?: $room->bot_score ?: 0);
        $opponentScore = $isPlayerOne ? (int) ($room->player_two_score ?: $room->bot_score ?: 0) : (int) ($room->player_one_score ?: $room->player_score ?: 0);
        $mySupportSpent = $isPlayerOne ? (int) ($room->player_one_support_spent ?? 0) : (int) ($room->player_two_support_spent ?? 0);
        $opponentSupportSpent = $isPlayerOne ? (int) ($room->player_two_support_spent ?? 0) : (int) ($room->player_one_support_spent ?? 0);

        $result = $room->status === 'finished'
            ? ($room->result_type === 'draw'
                ? 'draw'
                : ((int) $room->winner_id === (int) $viewer->id ? 'win' : ($room->result_type === 'cancelled' ? 'cancelled' : 'lose')))
            : null;

        $countdownRemaining = 0;
        if ($room->status === 'countdown' && $room->started_at) {
            $countdownRemaining = max(0, (int) ceil(($room->started_at->getTimestamp() - now()->getTimestamp())));
        }

        $remainingSeconds = 0;
        if (in_array($room->status, ['countdown', 'active'], true) && $room->ends_at) {
            $remainingSeconds = max(0, (int) ceil(($room->ends_at->getTimestamp() - now()->getTimestamp())));
        }

        return [
            'id' => $room->id,
            'status' => $room->status,
            'mode' => $room->mode,
            'is_bot' => $room->mode === 'bot',
            'room_tier' => $room->room_tier ?: 'silver',
            'stake_amount' => (int) ($room->stake_amount ?: $this->stakeForTier((string) ($room->room_tier ?: 'silver'))),
            'opponent_name' => $this->opponentNameForViewer($room, $viewer),
            'player_score' => $myScore,
            'bot_score' => $opponentScore,
            'my_score' => $myScore,
            'opponent_score' => $opponentScore,
            'my_support_spent' => $mySupportSpent,
            'opponent_support_spent' => $opponentSupportSpent,
            'duration_seconds' => (int) $room->duration_seconds,
            'started_at' => optional($room->started_at)?->toIso8601String(),
            'ends_at' => optional($room->ends_at)?->toIso8601String(),
            'finished_at' => optional($room->finished_at)?->toIso8601String(),
            'result' => $result,
            'reward' => $result === 'win' ? (int) ($room->winner_reward ?: $room->reward ?: 0) : ($result === 'draw' ? (int) ($room->reward ?: 0) : (int) ($room->loser_reward ?: 0)),
            'winner_reward' => (int) ($room->winner_reward ?: 0),
            'loser_reward' => (int) ($room->loser_reward ?: 0),
            'fee_amount' => (int) ($room->fee_amount ?: 0),
            'support_spent' => (int) ($room->support_spent ?: 0),
            'countdown_remaining' => $countdownRemaining,
            'remaining_seconds' => $remainingSeconds,
        ];
    }

    private function opponentNameForViewer(BattleRoom $room, TelegramUser $viewer): string
    {
        if ($room->mode === 'bot') {
            return $room->opponent_name ?: 'Arena Bot';
        }

        $opponentId = (int) $room->player_one_id === (int) $viewer->id
            ? $room->player_two_id
            : $room->player_one_id;

        $opponent = $opponentId ? TelegramUser::query()->find($opponentId) : null;
        return $opponent?->first_name ?: ($room->opponent_name ?: 'Player');
    }

    private function scoreFieldForUser(BattleRoom $room, TelegramUser $user): string
    {
        return ((int) $room->player_two_id === (int) $user->id) ? 'player_two_score' : 'player_one_score';
    }

    private function supportFieldForUser(BattleRoom $room, TelegramUser $user): string
    {
        return ((int) $room->player_two_id === (int) $user->id) ? 'player_two_support_spent' : 'player_one_support_spent';
    }

    private function stakeForTier(string $tier): int
    {
        return self::ROOM_TIERS[$tier] ?? self::ROOM_TIERS['silver'];
    }

    private function userState(TelegramUser $user): array
    {
        return [
            'balance' => (float) $user->balance,
            'available_energy' => (int) $user->available_energy,
        ];
    }
}
