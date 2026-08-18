<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('wallet_type', 20)->default('customer');
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('available_balance', 15, 2)->default(0.00);
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 20)->default('active');
            $table->decimal('daily_limit', 15, 2)->default(50000.00);
            $table->decimal('single_txn_limit', 15, 2)->default(20000.00);
            $table->string('mmo_partner', 50);
            $table->string('mmo_wallet_id');
            $table->timestamps();
            $table->unique(['mmo_partner', 'mmo_wallet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
