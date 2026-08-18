<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajo_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->foreign('group_id')->references('id')->on('ajo_groups');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->decimal('amount', 15, 2);
            $table->integer('cycle_number');
            $table->string('status', 20)->default('pending');
            $table->uuid('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_payouts');
    }
};
