<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajo_contributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->foreign('group_id')->references('id')->on('ajo_groups');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('logged_by_agent_id')->nullable();
            $table->foreign('logged_by_agent_id')->references('id')->on('agents');
            $table->decimal('amount', 15, 2);
            $table->integer('cycle_number');
            $table->string('status', 20)->default('completed');
            $table->uuid('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_contributions');
    }
};
