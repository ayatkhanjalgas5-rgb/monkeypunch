<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_leaderboard_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('week_key')->unique();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_leaderboard_cycles');
    }
};
