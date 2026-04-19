<?php

namespace Database\Seeders;

use App\Models\DailyTask;
use Illuminate\Database\Seeder;

class DailyTaskSeeder extends Seeder
{
    public function run(): void
    {
        $dailyTasks = [
            ['id' => 1, 'name' => 'Day 1', 'reward_coins' => 250, 'required_login_streak' => 1],
            ['id' => 2, 'name' => 'Day 2', 'reward_coins' => 400, 'required_login_streak' => 2],
            ['id' => 3, 'name' => 'Day 3', 'reward_coins' => 600, 'required_login_streak' => 3],
            ['id' => 4, 'name' => 'Day 4', 'reward_coins' => 900, 'required_login_streak' => 4],
            ['id' => 5, 'name' => 'Day 5', 'reward_coins' => 1_250, 'required_login_streak' => 5],
            ['id' => 6, 'name' => 'Day 6', 'reward_coins' => 1_750, 'required_login_streak' => 6],
            ['id' => 7, 'name' => 'Day 7', 'reward_coins' => 2_500, 'required_login_streak' => 7],
            ['id' => 8, 'name' => 'Day 8', 'reward_coins' => 3_500, 'required_login_streak' => 8],
            ['id' => 9, 'name' => 'Day 9', 'reward_coins' => 4_500, 'required_login_streak' => 9],
            ['id' => 10, 'name' => 'Day 10', 'reward_coins' => 6_000, 'required_login_streak' => 10],
            ['id' => 11, 'name' => 'Day 11', 'reward_coins' => 7_500, 'required_login_streak' => 11],
            ['id' => 12, 'name' => 'Day 12', 'reward_coins' => 9_500, 'required_login_streak' => 12],
            ['id' => 13, 'name' => 'Day 13', 'reward_coins' => 12_000, 'required_login_streak' => 13],
            ['id' => 14, 'name' => 'Day 14', 'reward_coins' => 15_000, 'required_login_streak' => 14],
            ['id' => 15, 'name' => 'Day 15', 'reward_coins' => 18_000, 'required_login_streak' => 15],
            ['id' => 16, 'name' => 'Day 16', 'reward_coins' => 22_000, 'required_login_streak' => 16],
        ];

        foreach ($dailyTasks as $task) {
            DailyTask::updateOrCreate(['id' => $task['id']], $task);
        }
    }
}
