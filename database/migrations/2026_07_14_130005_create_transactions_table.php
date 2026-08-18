<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference', 100)->unique();
            $table->string('transaction_type', 30);
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0.00);
            $table->decimal('commission', 15, 2)->default(0.00);
            $table->string('status', 20)->default('pending');
            $table->uuid('from_wallet_id')->nullable();
            $table->foreign('from_wallet_id')->references('id')->on('wallets');
            $table->uuid('to_wallet_id')->nullable();
            $table->foreign('to_wallet_id')->references('id')->on('wallets');
            $table->uuid('agent_id')->nullable();
            $table->foreign('agent_id')->references('id')->on('users');
            $table->string('recipient_phone', 15)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('mmo_partner', 50)->nullable();
            $table->string('mmo_transaction_id')->nullable();
            $table->string('device_id')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();
            $table->index('reference');
            $table->index('transaction_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
