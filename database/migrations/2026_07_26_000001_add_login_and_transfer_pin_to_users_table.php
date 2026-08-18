<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_pin_hash')->nullable()->after('pin_hash');
            $table->string('transfer_pin_hash')->nullable()->after('login_pin_hash');
        });

        // Migrate existing pin_hash to both new columns
        DB::table('users')
            ->whereNotNull('pin_hash')
            ->update([
                'login_pin_hash' => DB::raw('pin_hash'),
                'transfer_pin_hash' => DB::raw('pin_hash'),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_pin_hash', 'transfer_pin_hash']);
        });
    }
};
