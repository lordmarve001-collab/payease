<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajo_owners', function (Blueprint $table) {
            $table->text('business_description')->nullable()->after('business_name');
            $table->string('business_address')->nullable()->after('business_description');
            $table->string('lga', 100)->nullable()->after('business_address');
            $table->string('state', 50)->nullable()->after('lga');
            $table->boolean('has_experience')->nullable()->after('state');
            $table->integer('planned_groups')->default(0)->after('has_experience');
            $table->integer('members_per_group')->default(0)->after('planned_groups');
            $table->string('agent_assignment_preference', 50)->nullable()->after('members_per_group');
            $table->string('reference_contact_name', 255)->nullable()->after('agent_assignment_preference');
            $table->string('reference_contact_phone', 10)->nullable()->after('reference_contact_name');
            $table->text('rejection_reason')->nullable()->after('reference_contact_phone');
            $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            $table->uuid('approved_by')->nullable()->after('approved_at');
            $table->foreign('approved_by')->references('id')->on('users');
        });

        // SQLite doesn't support ALTER COLUMN — change default via raw SQL where supported
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE ajo_owners ALTER COLUMN status DROP DEFAULT");
            DB::statement("ALTER TABLE ajo_owners ALTER COLUMN status SET DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('ajo_owners', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'business_description',
                'business_address',
                'lga',
                'state',
                'has_experience',
                'planned_groups',
                'members_per_group',
                'agent_assignment_preference',
                'reference_contact_name',
                'reference_contact_phone',
                'rejection_reason',
                'approved_at',
                'approved_by',
            ]);
        });
    }
};
