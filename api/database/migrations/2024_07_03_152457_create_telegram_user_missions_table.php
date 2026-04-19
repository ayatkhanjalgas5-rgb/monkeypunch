<?php

use App\Models\TelegramUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('telegram_user_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TelegramUser::class)->constrained();
            $table->foreignId('mission_level_id')->constrained('mission_levels');
            $table->unsignedTinyInteger('level');

            $table->unique(['telegram_user_id', 'mission_level_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_user_missions');
    }
};
