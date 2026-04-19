<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_reward_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained('telegram_users')->cascadeOnDelete();
            $table->foreignId('weekly_leaderboard_cycle_id')->constrained('weekly_leaderboard_cycles')->cascadeOnDelete();
            $table->unsignedInteger('rank');
            $table->unsignedBigInteger('diamonds_reward');
            $table->unsignedBigInteger('score')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

$table->unique(
    ['telegram_user_id', 'weekly_leaderboard_cycle_id'],
    'weekly_reward_unique'
);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reward_logs');
    }
};
