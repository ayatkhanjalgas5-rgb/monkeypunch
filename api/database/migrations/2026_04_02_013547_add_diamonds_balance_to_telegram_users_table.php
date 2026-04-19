<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_users', 'diamonds_balance')) {
                $table->unsignedBigInteger('diamonds_balance')->default(0)->after('balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            if (Schema::hasColumn('telegram_users', 'diamonds_balance')) {
                $table->dropColumn('diamonds_balance');
            }
        });
    }
};
