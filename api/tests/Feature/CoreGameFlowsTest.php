<?php

namespace Tests\Feature;

use App\Models\DailyTask;
use App\Models\Level;
use App\Models\ReferralTask;
use App\Models\Task;
use App\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CoreGameFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Level::create([
            'id' => 1,
            'level' => 1,
            'name' => 'Rookie',
            'from_balance' => 0,
            'to_balance' => 50000,
        ]);
    }

    public function test_telegram_auth_returns_token_and_starting_balance(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');

        $payload = [
            'auth_date' => (string) now()->timestamp,
            'query_id' => 'AAEAAAE',
            'user' => json_encode([
                'id' => 987654,
                'first_name' => 'Kerek',
                'last_name' => 'Joh',
                'username' => 'kerekjoh',
                'is_premium' => true,
            ], JSON_UNESCAPED_SLASHES),
        ];

        $response = $this->postJson('/api/auth/telegram-user', [
            'init_data' => $this->signedInitData($payload, 'test-bot-token'),
        ]);

        $response->assertOk()
            ->assertJsonPath('telegram_verified', true)
            ->assertJsonPath('auth_mode', 'telegram')
            ->assertJsonPath('first_login', true);

        $user = TelegramUser::where('telegram_id', 987654)->firstOrFail();
        $this->assertSame(5000.0, (float) $user->balance);
        $this->assertDatabaseHas('transactions', [
            'telegram_user_id' => $user->id,
            'type' => 'admin',
        ]);
    }

    public function test_user_can_claim_daily_task_reward(): void
    {
        $user = $this->createTelegramUser(['login_streak' => 2, 'balance' => 1000]);
        Sanctum::actingAs($user);

        $task = DailyTask::create([
            'name' => 'Day 2',
            'required_login_streak' => 2,
            'reward_coins' => 700,
        ]);

        $response = $this->postJson('/api/clicker/claim-daily-task');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('balance', 1700);

        $this->assertDatabaseHas('telegram_user_daily_tasks', [
            'telegram_user_id' => $user->id,
            'daily_task_id' => $task->id,
            'completed' => true,
        ]);
    }

    public function test_user_can_open_and_claim_regular_task_reward(): void
    {
        config()->set('admin.task_claim_cooldown_seconds', 0);

        $user = $this->createTelegramUser(['balance' => 2000]);
        Sanctum::actingAs($user);

        $task = Task::create([
            'name' => 'Join Telegram',
            'description' => 'Open the channel',
            'reward_coins' => 300,
            'type' => 'other',
            'action_name' => 'Open',
            'link' => 'https://t.me/example',
        ]);

        $this->postJson("/api/clicker/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $claimResponse = $this->postJson("/api/clicker/tasks/{$task->id}/claim");

        $claimResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('balance', 2300);

        $this->assertDatabaseHas('telegram_user_tasks', [
            'telegram_user_id' => $user->id,
            'task_id' => $task->id,
            'is_rewarded' => true,
        ]);
    }

    public function test_user_can_complete_referral_task_when_requirements_met(): void
    {
        $user = $this->createTelegramUser(['telegram_id' => 2001, 'balance' => 500]);
        Sanctum::actingAs($user);

        $this->createTelegramUser(['telegram_id' => 3001, 'referred_by' => 2001]);
        $this->createTelegramUser(['telegram_id' => 3002, 'referred_by' => 2001]);

        $task = ReferralTask::create([
            'title' => 'Bring 2 friends',
            'number_of_referrals' => 2,
            'reward' => 900,
        ]);

        $response = $this->postJson("/api/clicker/referral-tasks/{$task->id}/complete");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1400.0, (float) $user->fresh()->balance);
        $this->assertDatabaseHas('telegram_user_referral_task', [
            'telegram_user_id' => $user->id,
            'referral_task_id' => $task->id,
            'is_completed' => true,
        ]);
    }

    public function test_user_can_create_withdraw_request(): void
    {
        $user = $this->createTelegramUser([
            'balance' => 50000,
            'production_per_hour' => 0,
            'ton_wallet' => 'UQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
        ]);
        Sanctum::actingAs($user);

        $withdrawResponse = $this->postJson('/api/clicker/withdraw-requests', [
            'amount' => 10000,
            'wallet_address' => 'UQBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB',
            'network' => 'TON',
        ]);

        $withdrawResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('balance', 40000);

        $this->assertDatabaseHas('withdraw_requests', [
            'telegram_user_id' => $user->id,
            'amount' => 10000,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('transactions', [
            'telegram_user_id' => $user->id,
            'type' => 'withdraw',
        ]);
    }

    private function createTelegramUser(array $attributes = []): TelegramUser
    {
        return TelegramUser::create(array_merge([
            'telegram_id' => fake()->unique()->numberBetween(100000, 999999),
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => fake()->userName(),
            'balance' => 0,
            'earn_per_tap' => 1,
            'available_energy' => 500,
            'max_energy' => 500,
            'multi_tap_level' => 1,
            'energy_limit_level' => 1,
            'production_per_hour' => 0,
            'level_id' => 1,
            'login_streak' => 1,
            'last_tap_date' => now(),
            'last_login_date' => now(),
        ], $attributes));
    }

    private function signedInitData(array $data, string $botToken): string
    {
        ksort($data);
        $checkString = collect($data)->map(fn ($value, $key) => $key.'='.$value)->implode("\n");
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $data['hash'] = hash_hmac('sha256', $checkString, $secretKey);

        return http_build_query($data);
    }
}
