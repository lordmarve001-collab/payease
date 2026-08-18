<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->timestamp('last_settlement_at')->nullable()->after('approved_at');
            $table->unsignedTinyInteger('settlement_frequency_days')->default(1)->after('last_settlement_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['last_settlement_at', 'settlement_frequency_days']);
        });
    }
};
