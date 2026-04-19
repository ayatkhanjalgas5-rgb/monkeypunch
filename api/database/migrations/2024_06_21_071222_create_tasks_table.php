<?php

use App\Models\TelegramUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('reward_coins');
            $table->enum('type', ['video', 'other']);
            $table->string('action_name');
            $table->string('link');
            $table->timestamps();
        });

        Schema::create('telegram_user_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TelegramUser::class)->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->boolean('is_submitted')->default(false);
            $table->boolean('is_rewarded')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_user_tasks');
        Schema::dropIfExists('tasks');
    }
}
