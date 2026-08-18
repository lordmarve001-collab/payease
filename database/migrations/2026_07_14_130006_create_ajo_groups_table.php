<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajo_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ajo_owner_id')->nullable();
            $table->foreign('ajo_owner_id')->references('id')->on('ajo_owners');
            $table->string('name');
            $table->decimal('contribution_amount', 15, 2);
            $table->string('frequency', 20);
            $table->integer('members_count');
            $table->string('payout_order', 20)->default('fixed');
            $table->uuid('managing_agent_id')->nullable();
            $table->foreign('managing_agent_id')->references('id')->on('agents');
            $table->string('status', 20)->default('pending');
            $table->date('start_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_groups');
    }
};
