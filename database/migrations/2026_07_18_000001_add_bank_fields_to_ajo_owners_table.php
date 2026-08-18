<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajo_owners', function (Blueprint $table) {
            $table->string('bank_name', 255)->nullable()->after('reference_contact_phone');
            $table->string('account_name', 255)->nullable()->after('bank_name');
            $table->string('account_number', 10)->nullable()->after('account_name');
        });
    }

    public function down(): void
    {
        Schema::table('ajo_owners', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_name', 'account_number']);
        });
    }
};
