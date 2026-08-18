<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->string('verification_provider', 50)->nullable()->after('submitted_by_agent_id');
            $table->string('verification_reference', 255)->nullable()->after('verification_provider');
            $table->decimal('match_confidence', 5, 2)->nullable()->after('verification_reference');
            $table->boolean('auto_verified')->default(false)->after('match_confidence');
            $table->json('verification_raw_response')->nullable()->after('auto_verified');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('identity_verification_consent_at')->nullable()->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->dropColumn([
                'verification_provider',
                'verification_reference',
                'match_confidence',
                'auto_verified',
                'verification_raw_response',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('identity_verification_consent_at');
        });
    }
};
