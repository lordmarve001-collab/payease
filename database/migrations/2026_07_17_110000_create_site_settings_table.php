<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('site_name', 255)->default('PayEase');
            $table->string('site_tagline', 255)->nullable();
            $table->text('site_description')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('icon_path', 500)->nullable();
            $table->string('favicon_path', 500)->nullable();
            $table->string('primary_color', 20)->default('#00A86B');
            $table->string('secondary_color', 20)->default('#0A0F1A');
            $table->string('accent_color', 20)->nullable();
            $table->string('support_email', 255)->nullable();
            $table->string('support_phone', 50)->nullable();
            $table->string('support_whatsapp', 50)->nullable();
            $table->string('address_line1', 500)->nullable();
            $table->string('address_line2', 500)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('state', 255)->nullable();
            $table->string('country', 255)->default('Nigeria');
            $table->json('social_links')->nullable();
            $table->json('business_hours')->nullable();
            $table->boolean('registration_enabled')->default(true);
            $table->string('default_user_role', 50)->default('customer');
            $table->json('registration_required_fields')->nullable();
            $table->boolean('email_verification_required')->default(false);
            $table->boolean('phone_verification_required')->default(true);
            $table->string('recaptcha_site_key', 500)->nullable();
            $table->string('recaptcha_secret_key', 500)->nullable();
            $table->text('custom_footer_html')->nullable();
            $table->text('custom_head_html')->nullable();
            $table->string('timezone', 100)->default('Africa/Lagos');
            $table->string('locale', 10)->default('en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
