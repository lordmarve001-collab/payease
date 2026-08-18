<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 50)->index();
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->string('action_label')->nullable();
            $table->string('severity', 20)->default('info');
            $table->boolean('is_read')->default(false);
            $table->uuid('related_id')->nullable();
            $table->string('related_type')->nullable();
            $table->timestamps();

            $table->index(['is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
