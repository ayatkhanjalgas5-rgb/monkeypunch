<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('battle_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('player_one_id')->nullable()->after('telegram_user_id');
            $table->unsignedBigInteger('player_two_id')->nullable()->after('player_one_id');

            $table->timestamp('player_one_ready_at')->nullable();
            $table->timestamp('player_two_ready_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('battle_rooms', function (Blueprint $table) {
            $table->dropColumn([
                'player_one_id',
                'player_two_id',
                'player_one_ready_at',
                'player_two_ready_at',
            ]);
        });
    }
};
