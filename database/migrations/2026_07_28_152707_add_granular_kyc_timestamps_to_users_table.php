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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('nin_verified_at')->nullable()->after('kyc_verified_at');
            $table->timestamp('bvn_verified_at')->nullable()->after('nin_verified_at');
            $table->timestamp('next_of_kin_submitted_at')->nullable()->after('bvn_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nin_verified_at', 'bvn_verified_at', 'next_of_kin_submitted_at']);
        });
    }
};
