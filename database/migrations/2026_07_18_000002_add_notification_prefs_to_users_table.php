<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_email')->default(true)->after('must_change_password');
            $table->boolean('notify_sms')->default(true)->after('notify_email');
            $table->boolean('notify_payout')->default(true)->after('notify_sms');
            $table->boolean('notify_contribution')->default(true)->after('notify_payout');
            $table->boolean('notify_agent_activity')->default(false)->after('notify_contribution');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_email',
                'notify_sms',
                'notify_payout',
                'notify_contribution',
                'notify_agent_activity',
            ]);
        });
    }
};
