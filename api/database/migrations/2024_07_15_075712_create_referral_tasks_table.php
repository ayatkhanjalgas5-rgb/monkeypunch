<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referral_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedTinyInteger('number_of_referrals');
            $table->unsignedBigInteger('reward');
            $table->timestamps();
        });

        Schema::create('telegram_user_referral_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_task_id')->constrained()->onDelete('cascade');
            $table->foreignId('telegram_user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_tasks');
        Schema::dropIfExists('telegram_user_referral_task');
    }
};
