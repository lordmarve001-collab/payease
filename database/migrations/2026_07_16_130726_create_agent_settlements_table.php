<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_settlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agent_id');
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->decimal('amount_declared', 15, 2);
            $table->string('bank_reference', 255)->nullable();
            $table->string('proof_of_deposit_url', 500)->nullable();
            $table->string('status', 20)->default('pending_verification');
            $table->uuid('verified_by')->nullable();
            $table->foreign('verified_by')->references('id')->on('users');
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('agent_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_settlements');
    }
};
