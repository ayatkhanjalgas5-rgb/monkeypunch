<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('battle_rooms', function (Blueprint $table) {
            $table->string('mode')->default('bot')->after('status'); // bot, pvp
            $table->string('bot_profile')->nullable()->after('mode'); // easy, medium, hard
            $table->timestamp('search_started_at')->nullable()->after('bot_profile');
        });
    }

    public function down(): void
    {
        Schema::table('battle_rooms', function (Blueprint $table) {
            $table->dropColumn(['mode', 'bot_profile', 'search_started_at']);
        });
    }
};