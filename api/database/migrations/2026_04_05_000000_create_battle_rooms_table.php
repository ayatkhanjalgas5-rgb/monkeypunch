<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battle_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->string('opponent_name')->nullable();
            $table->unsignedBigInteger('player_score')->default(0);
            $table->unsignedBigInteger('bot_score')->default(0);
            $table->unsignedInteger('duration_seconds')->default(60);
            $table->unsignedBigInteger('reward')->default(0);
            $table->unsignedBigInteger('support_spent')->default(0);
            $table->string('result')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battle_rooms');
    }
};
