<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('alert_type'); // velocity, anomalous_amount, device_mismatch, location_anomaly, duplicate_ip
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->string('entity_type')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->text('description');
            $table->json('context')->nullable();
            $table->string('status')->default('new'); // new, investigating, confirmed, dismissed
            $table->uuid('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('alert_type');
            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_alerts');
    }
};
