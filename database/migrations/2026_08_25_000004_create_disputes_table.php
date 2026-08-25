<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('transaction_id')->nullable();
            $table->string('category'); // incorrect_amount, unreceived_funds, duplicate_charge, unauthorized, other
            $table->string('subject');
            $table->text('description');
            $table->decimal('disputed_amount', 12, 2)->nullable();
            $table->string('status')->default('open'); // open, under_review, resolved, rejected, escalated
            $table->string('resolution')->nullable(); // refund, credit, no_action
            $table->text('admin_notes')->nullable();
            $table->text('user_notes')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
