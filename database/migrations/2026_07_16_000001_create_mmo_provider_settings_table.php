<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mmo_provider_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 20)->unique();
            $table->boolean('is_active')->default(false);
            $table->string('environment', 20)->default('sandbox');
            $table->longText('credentials')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 20)->default('untested');
            $table->text('last_test_message')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('mmo_provider_settings')->insert([
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'provider' => 'monnify',
                'is_active' => true,
                'environment' => 'sandbox',
                'credentials' => null,
                'last_test_status' => 'untested',
                'last_test_message' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'provider' => 'opay',
                'is_active' => false,
                'environment' => 'sandbox',
                'credentials' => null,
                'last_test_status' => 'untested',
                'last_test_message' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'provider' => 'palmpay',
                'is_active' => false,
                'environment' => 'sandbox',
                'credentials' => null,
                'last_test_status' => 'untested',
                'last_test_message' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mmo_provider_settings');
    }
};
