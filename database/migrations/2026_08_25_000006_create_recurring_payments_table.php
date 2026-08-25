<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('payment_type'); // airtime, data, cable, electricity, transfer
            $table->decimal('amount', 12, 2);
            $table->string('frequency'); // daily, weekly, monthly
            $table->json('payment_details');
            $table->string('status')->default('active'); // active, paused, cancelled, completed
            $table->string('transfer_pin_hash')->nullable();
            $table->timestamp('next_execution_at')->nullable();
            $table->timestamp('last_executed_at')->nullable();
            $table->integer('executions_count')->default(0);
            $table->integer('max_executions')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_execution_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_payments');
    }
};
