<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_users', 'is_suspicious')) {
                $table->boolean('is_suspicious')->default(false)->after('created_ip');
            }

            if (!Schema::hasColumn('telegram_users', 'suspicious_score')) {
                $table->unsignedInteger('suspicious_score')->default(0)->after('is_suspicious');
            }
        });

        Schema::table('withdraw_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('withdraw_requests', 'client_request_id')) {
                $table->string('client_request_id', 120)->nullable()->after('network');
            }

            if (!Schema::hasColumn('withdraw_requests', 'verification_status')) {
                $table->string('verification_status', 32)->default('pending')->after('tx_hash');
            }

            if (!Schema::hasColumn('withdraw_requests', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            foreach (['is_suspicious', 'suspicious_score'] as $column) {
                if (Schema::hasColumn('telegram_users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('withdraw_requests', function (Blueprint $table) {
            foreach (['client_request_id', 'verification_status', 'verified_at'] as $column) {
                if (Schema::hasColumn('withdraw_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
