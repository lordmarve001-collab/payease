<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_deletion_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agent_id');
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->uuid('requested_by_user_id');
            $table->foreign('requested_by_user_id')->references('id')->on('users');
            $table->uuid('reviewed_by_user_id')->nullable();
            $table->foreign('reviewed_by_user_id')->references('id')->on('users');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_deletion_requests');
    }
};
