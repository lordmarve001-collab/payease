<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->string('account_number')->nullable()->after('mmo_wallet_id');
            $table->string('provider_reference')->nullable()->after('account_number');
            $table->json('provider_metadata')->nullable()->after('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['account_number', 'provider_reference', 'provider_metadata']);
        });
    }
};
