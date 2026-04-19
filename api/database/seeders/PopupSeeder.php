<?php

namespace Database\Seeders;

use App\Models\Popup;
use Illuminate\Database\Seeder;

class PopupSeeder extends Seeder
{
    public function run(): void
    {
        Popup::updateOrCreate(
            ['title' => 'Follow us on X'],
            [
                'text' => 'Follow us on X and get 10,000 coins. It takes seconds.',
                'button_text' => 'Follow',
                'button_link' => 'https://x.com',
            ],
        );
    }
}
