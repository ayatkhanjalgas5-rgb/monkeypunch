<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 50);
            $table->decimal('amount', 20, 4);
            $table->decimal('balance_before', 20, 4);
            $table->decimal('balance_after', 20, 4);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['telegram_user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
