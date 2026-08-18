<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ajo_group_agents');

        Schema::create('ajo_group_agents', function (Blueprint $table) {
            $table->uuid('ajo_group_id');
            $table->foreign('ajo_group_id')->references('id')->on('ajo_groups')->onDelete('cascade');
            $table->uuid('agent_id');
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->string('role', 30)->default('field_agent');
            $table->timestamps();

            $table->unique(['ajo_group_id', 'agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_group_agents');

        Schema::create('ajo_group_agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ajo_group_id');
            $table->foreign('ajo_group_id')->references('id')->on('ajo_groups')->onDelete('cascade');
            $table->uuid('agent_id');
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->string('role', 30)->default('field_agent');
            $table->timestamps();

            $table->unique(['ajo_group_id', 'agent_id']);
        });
    }
};
