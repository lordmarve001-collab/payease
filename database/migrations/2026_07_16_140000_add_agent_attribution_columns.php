<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('registered_by_agent_id')->nullable()->after('referred_by');
            $table->foreign('registered_by_agent_id')->references('id')->on('agents')->nullOnDelete();
        });

        Schema::table('kyc_documents', function (Blueprint $table): void {
            $table->uuid('submitted_by_agent_id')->nullable()->after('verified_at');
            $table->foreign('submitted_by_agent_id')->references('id')->on('agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kyc_documents', function (Blueprint $table): void {
            $table->dropForeign(['submitted_by_agent_id']);
            $table->dropColumn('submitted_by_agent_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['registered_by_agent_id']);
            $table->dropColumn('registered_by_agent_id');
        });
    }
};
