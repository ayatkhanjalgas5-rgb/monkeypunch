<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_users', 'telegram_auth_date')) {
                $table->timestamp('telegram_auth_date')->nullable()->after('telegram_id');
            }

            if (!Schema::hasColumn('telegram_users', 'is_premium')) {
                $table->boolean('is_premium')->default(false)->after('username');
            }

            if (!Schema::hasColumn('telegram_users', 'wallet_provider')) {
                $table->string('wallet_provider')->nullable()->after('ton_wallet');
            }
        });

        Schema::table('withdraw_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('withdraw_requests', 'tx_hash')) {
                $table->string('tx_hash')->nullable()->after('status');
            }

            if (!Schema::hasColumn('withdraw_requests', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('processed_at');
            }

            if (!Schema::hasColumn('withdraw_requests', 'network')) {
                $table->string('network')->default('TON')->after('wallet_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            foreach (['telegram_auth_date', 'is_premium', 'wallet_provider'] as $column) {
                if (Schema::hasColumn('telegram_users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('withdraw_requests', function (Blueprint $table) {
            foreach (['tx_hash', 'paid_at', 'network'] as $column) {
                if (Schema::hasColumn('withdraw_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
