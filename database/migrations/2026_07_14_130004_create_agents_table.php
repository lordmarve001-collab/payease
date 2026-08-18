<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('ajo_owner_id')->nullable();
            $table->foreign('ajo_owner_id')->references('id')->on('ajo_owners');
            $table->string('business_name');
            $table->text('business_address');
            $table->decimal('gps_latitude', 10, 8);
            $table->decimal('gps_longitude', 11, 8);
            $table->string('lga', 100);
            $table->string('state', 50);
            $table->decimal('float_balance', 15, 2)->default(0.00);
            $table->decimal('max_float', 15, 2)->default(100000.00);
            $table->decimal('commission_rate', 5, 2)->default(1.50);
            $table->decimal('total_earnings', 15, 2)->default(0.00);
            $table->string('status', 20)->default('pending');
            $table->string('id_document_url')->nullable();
            $table->string('shop_photo_url')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index('ajo_owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
