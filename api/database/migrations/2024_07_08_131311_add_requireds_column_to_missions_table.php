<?php

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
        Schema::table('missions', function (Blueprint $table) {
            $table->unsignedInteger('required_user_level')->default(0)->after('image');
            $table->unsignedInteger('required_friends_invitation')->default(0)->after('required_user_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn('required_user_level');
            $table->dropColumn('required_friends_invitation');
        });
    }
};
