<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('daily_tasks')) {
            $rewards = [
                1 => 250,
                2 => 400,
                3 => 600,
                4 => 900,
                5 => 1250,
                6 => 1750,
                7 => 2500,
                8 => 3500,
                9 => 4500,
                10 => 6000,
                11 => 7500,
                12 => 9500,
                13 => 12000,
                14 => 15000,
                15 => 18000,
                16 => 22000,
            ];

            foreach ($rewards as $id => $reward) {
                DB::table('daily_tasks')->where('id', $id)->update(['reward_coins' => $reward]);
            }
        }

        if (Schema::hasTable('referral_tasks')) {
            DB::table('referral_tasks')->where('number_of_referrals', 3)->update(['reward' => 4000, 'title' => 'Invite 3 friends']);
            DB::table('referral_tasks')->where('number_of_referrals', 6)->update(['reward' => 10000, 'title' => 'Invite 6 friends']);
        }

        if (Schema::hasTable('levels')) {
            $levels = [
                1 => ['Starter', 1000, 8000],
                2 => ['Brawler', 8000, 20000],
                3 => ['Street King', 20000, 45000],
                4 => ['Arena Rookie', 45000, 85000],
                5 => ['Arena Pro', 85000, 150000],
                6 => ['Iron Fist', 150000, 260000],
                7 => ['Elite Striker', 260000, 420000],
                8 => ['Cage Beast', 420000, 650000],
                9 => ['Monkey Lord', 650000, 950000],
                10 => ['Legend', 950000, 1500000],
            ];

            foreach ($levels as $level => [$name, $from, $to]) {
                DB::table('levels')->updateOrInsert(
                    ['level' => $level],
                    ['name' => $name, 'from_balance' => $from, 'to_balance' => $to, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        // Keep non-destructive on live app.
    }
};
