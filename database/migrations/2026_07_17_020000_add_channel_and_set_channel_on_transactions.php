<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('channel', 20)->default('web')
                ->after('description');
        });

        // Index for admin analytics
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['channel']);
            $table->dropColumn('channel');
        });
    }
};
