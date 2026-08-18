<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table): void {
            $table->string('wallet_account_number')->nullable()->after('account_number');
            $table->string('mmo_wallet_id')->nullable()->change();
        });

        DB::table('wallets')
            ->whereNull('wallet_account_number')
            ->update([
                'wallet_account_number' => DB::raw('account_number'),
            ]);
    }

    public function down(): void
    {
        DB::table('wallets')
            ->whereNull('mmo_wallet_id')
            ->update([
                'mmo_wallet_id' => DB::raw("COALESCE(provider_reference, wallet_account_number, account_number, 'legacy-wallet')"),
            ]);

        Schema::table('wallets', function (Blueprint $table): void {
            $table->dropColumn('wallet_account_number');
            $table->string('mmo_wallet_id')->nullable(false)->change();
        });
    }
};
