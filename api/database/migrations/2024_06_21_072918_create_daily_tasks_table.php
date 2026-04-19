<?php

use App\Models\TelegramUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyTasksTable extends Migration
{
    public function up()
    {
        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('required_login_streak');
            $table->integer('reward_coins');
            $table->timestamps();
        });

        Schema::create('telegram_user_daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TelegramUser::class)->constrained()->onDelete('cascade');
            $table->foreignId('daily_task_id')->constrained()->onDelete('cascade');
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_user_daily_tasks');
        Schema::dropIfExists('daily_tasks');
    }
}
