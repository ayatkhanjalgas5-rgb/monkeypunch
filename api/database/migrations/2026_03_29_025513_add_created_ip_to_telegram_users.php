<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->string('created_ip')->nullable()->index()->after('referred_by');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropColumn('created_ip');
        });
    }
};
