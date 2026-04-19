<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = [
            [
                'name' => 'Watch Tutorial Video',
                'description' => 'Watch our game tutorial video on YouTube.',
                'reward_coins' => 100,
                'link' => 'https://youtube.com',
                'type' => 'video',
                'action_name' => 'Watch Video',
            ],
            [
                'name' => 'Join Our Discord',
                'description' => 'Join our official Discord server and say hello in the #welcome channel.',
                'reward_coins' => 100,
                'link' => 'https://discord.gg/yourgame',
                'type' => 'other',
                'action_name' => 'Join',
            ],
            [
                'name' => 'Follow on Twitter',
                'description' => 'Follow our official Twitter account and retweet our pinned tweet.',
                'reward_coins' => 150,
                'link' => 'https://twitter.com/yourgame',
                'type' => 'other',
                'action_name' => 'Follow',
            ],
            [
                'name' => 'Like Facebook Page',
                'description' => 'Like our Facebook page and leave a comment on our latest post.',
                'reward_coins' => 100,
                'link' => 'https://facebook.com/yourgame',
                'type' => 'other',
                'action_name' => 'Like',
            ],
            [
                'name' => 'Follow on Instagram',
                'description' => 'Follow our Instagram account and like our most recent post.',
                'reward_coins' => 125,
                'link' => 'https://instagram.com/yourgame',
                'type' => 'other',
                'action_name' => 'Follow',
            ],
            [
                'name' => 'Join Telegram Group',
                'description' => 'Join our Telegram group and introduce yourself.',
                'reward_coins' => 175,
                'link' => 'https://t.me/yourgame',
                'type' => 'other',
                'action_name' => 'Join',
            ],
            [
                'name' => 'Sign Up for Newsletter',
                'description' => 'Subscribe to our weekly newsletter for game updates and tips.',
                'reward_coins' => 80,
                'link' => 'https://yourgame.com/newsletter',
                'type' => 'other',
                'action_name' => 'Subscribe',
            ],
            [
                'name' => 'Review on App Store',
                'description' => 'Leave a review for our game on the App Store or Google Play Store.',
                'reward_coins' => 300,
                'link' => 'https://yourgame.com/review',
                'type' => 'other',
                'action_name' => 'Review',
            ],
        ];

        foreach ($tasks as $task) {
            Task::updateOrCreate(
                ['name' => $task['name']],
                $task,
            );
        }
    }
}
