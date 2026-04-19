<?php

namespace Database\Seeders;

use App\Models\ReferralTask;
use Illuminate\Database\Seeder;

class ReferralTaskSeeder extends Seeder
{
    public function run()
    {
        $tasks = [
            [
                'title' => 'Invite 3 friends',
                'number_of_referrals' => 3,
                'reward' => 4_000,
            ],
            [
                'title' => 'Invite 6 friends',
                'number_of_referrals' => 6,
                'reward' => 10_000,
            ],
        ];

        foreach ($tasks as $task) {
            ReferralTask::updateOrCreate(['title' => $task['title']], $task);
        }
    }
}
