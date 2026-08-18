<?php

use App\Helpers\PhoneNumberHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone_number', 10)->change();
            $table->string('next_of_kin_name', 255)->nullable()->after('kyc_verified_at');
            $table->string('next_of_kin_relationship', 50)->nullable()->after('next_of_kin_name');
            $table->string('next_of_kin_phone', 10)->nullable()->after('next_of_kin_relationship');
        });

        DB::table('users')->orderBy('id')->each(function (object $user): void {
            try {
                $normalized = PhoneNumberHelper::normalize($user->phone_number);
                DB::table('users')->where('id', $user->id)->update(['phone_number' => $normalized]);
            } catch (\InvalidArgumentException) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['next_of_kin_name', 'next_of_kin_relationship', 'next_of_kin_phone']);
            $table->string('phone_number', 20)->change();
        });
    }
};
