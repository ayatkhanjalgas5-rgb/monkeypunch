<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['level' => 1, 'name' => 'Starter', 'from_balance' => 1_000, 'to_balance' => 8_000],
            ['level' => 2, 'name' => 'Brawler', 'from_balance' => 8_000, 'to_balance' => 20_000],
            ['level' => 3, 'name' => 'Street King', 'from_balance' => 20_000, 'to_balance' => 45_000],
            ['level' => 4, 'name' => 'Arena Rookie', 'from_balance' => 45_000, 'to_balance' => 85_000],
            ['level' => 5, 'name' => 'Arena Pro', 'from_balance' => 85_000, 'to_balance' => 150_000],
            ['level' => 6, 'name' => 'Iron Fist', 'from_balance' => 150_000, 'to_balance' => 260_000],
            ['level' => 7, 'name' => 'Elite Striker', 'from_balance' => 260_000, 'to_balance' => 420_000],
            ['level' => 8, 'name' => 'Cage Beast', 'from_balance' => 420_000, 'to_balance' => 650_000],
            ['level' => 9, 'name' => 'Monkey Lord', 'from_balance' => 650_000, 'to_balance' => 950_000],
            ['level' => 10, 'name' => 'Legend', 'from_balance' => 950_000, 'to_balance' => 1_500_000],
        ];

        foreach ($levels as $level) {
            Level::updateOrCreate(['level' => $level['level']], $level);
        }
    }
}
