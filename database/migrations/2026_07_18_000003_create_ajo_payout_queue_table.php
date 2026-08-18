<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajo_payout_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ajo_payout_id');
            $table->uuid('group_id');
            $table->uuid('member_user_id');
            $table->uuid('agent_id');
            $table->decimal('amount', 15, 2);
            $table->integer('cycle_number');
            $table->string('status', 20)->default('pending'); // pending, processing, completed, failed
            $table->text('note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->uuid('processed_by')->nullable();
            $table->timestamps();

            $table->foreign('ajo_payout_id')->references('id')->on('ajo_payouts')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('ajo_groups');
            $table->foreign('member_user_id')->references('id')->on('users');
            $table->foreign('agent_id')->references('id')->on('agents');
            $table->foreign('processed_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_payout_queue');
    }
};
