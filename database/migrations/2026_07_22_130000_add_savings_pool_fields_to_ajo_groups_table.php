<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajo_groups', function (Blueprint $table) {
            $table->string('model_type', 20)->default('rotational')->after('name');
            $table->text('description')->nullable()->after('model_type');
            $table->decimal('owner_fee_percentage', 5, 2)->default(0)->after('contribution_amount');
            $table->integer('collection_period_days')->nullable()->after('owner_fee_percentage');
            $table->date('collection_end_date')->nullable()->after('collection_period_days');
            $table->decimal('min_contribution', 15, 2)->nullable()->after('collection_end_date');
            $table->decimal('max_contribution', 15, 2)->nullable()->after('min_contribution');
            $table->decimal('target_pool_amount', 15, 2)->nullable()->after('max_contribution');
        });
    }

    public function down(): void
    {
        Schema::table('ajo_groups', function (Blueprint $table) {
            $table->dropColumn([
                'model_type',
                'description',
                'owner_fee_percentage',
                'collection_period_days',
                'collection_end_date',
                'min_contribution',
                'max_contribution',
                'target_pool_amount',
            ]);
        });
    }
};
