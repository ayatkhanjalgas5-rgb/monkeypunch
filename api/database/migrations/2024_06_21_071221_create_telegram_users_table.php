<?php

use App\Models\Level;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_id')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->string('ton_wallet')->nullable();
            $table->integer('balance')->default(0);
            $table->integer('earn_per_tap')->default(1);
            $table->integer('available_energy')->default(500);
            $table->integer('max_energy')->default(500);
            $table->integer('multi_tap_level')->default(1);
            $table->integer('energy_limit_level')->default(1);
            $table->boolean('booster_pack_2x')->default(false);
            $table->boolean('booster_pack_3x')->default(false);
            $table->boolean('booster_pack_7x')->default(false);
            $table->dateTime('booster_pack_active_until')->nullable();
            $table->integer('login_streak')->default(0);
            $table->integer('daily_booster_uses')->default(0);
            $table->timestamp('last_daily_booster_use')->nullable();
            $table->unsignedInteger('production_per_hour')->default(0);
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->foreignIdFor(Level::class)->default(1);
            $table->rememberToken();
            $table->dateTime('last_tap_date')->nullable();
            $table->dateTime('last_login_date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_users');
    }
};
