<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone_number', 15)->unique();
            $table->string('full_name');
            $table->string('bvn', 11)->nullable();
            $table->string('nin', 11)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('lga', 100)->nullable();
            $table->string('state', 50)->nullable();
            $table->integer('kyc_level')->default(0);
            $table->timestamp('kyc_verified_at')->nullable();
            $table->string('pin_hash')->nullable();
            $table->string('device_id')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('referral_code', 20)->unique()->nullable();
            $table->uuid('referred_by')->nullable();
            $table->foreign('referred_by')->references('id')->on('users');
            $table->timestamps();
            $table->index('phone_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
