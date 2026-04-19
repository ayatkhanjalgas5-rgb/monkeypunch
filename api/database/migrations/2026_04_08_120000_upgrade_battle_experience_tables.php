<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('battle_queue')) {
            if (!Schema::hasColumn('battle_queue', 'room_tier')) {
                Schema::table('battle_queue', function (Blueprint $table) {
                    $table->string('room_tier')->default('silver')->after('telegram_user_id');
                });
            }

            if (!Schema::hasColumn('battle_queue', 'stake_amount')) {
                Schema::table('battle_queue', function (Blueprint $table) {
                    $table->unsignedBigInteger('stake_amount')->default(1000)->after('room_tier');
                });
            }
        }

        if (Schema::hasTable('battle_rooms')) {
            if (!Schema::hasColumn('battle_rooms', 'room_tier')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->string('room_tier')->default('silver')->after('mode');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'stake_amount')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->unsignedBigInteger('stake_amount')->default(1000)->after('room_tier');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'countdown_started_at')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->timestamp('countdown_started_at')->nullable()->after('search_started_at');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'player_one_score')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->unsignedBigInteger('player_one_score')->default(0)->after('player_two_id');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'player_two_score')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->unsignedBigInteger('player_two_score')->default(0)->after('player_one_score');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'player_one_support_spent')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->unsignedBigInteger('player_one_support_spent')->default(0)->after('player_two_score');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'player_two_support_spent')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->unsignedBigInteger('player_two_support_spent')->default(0)->after('player_one_support_spent');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'winner_id')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->unsignedBigInteger('winner_id')->nullable()->after('player_two_ready_at');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'result_type')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->string('result_type')->nullable()->after('winner_id');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'winner_reward')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->unsignedBigInteger('winner_reward')->default(0)->after('result_type');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'loser_reward')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->unsignedBigInteger('loser_reward')->default(0)->after('winner_reward');
                });
            }

            if (!Schema::hasColumn('battle_rooms', 'fee_amount')) {
                Schema::table('battle_rooms', function (Blueprint $table) {
                    $table->unsignedBigInteger('fee_amount')->default(0)->after('loser_reward');
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank to avoid destructive rollbacks on a live app.
    }
};
