<?php

namespace App\Providers;

use App\Models\SiteSetting;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use App\Contracts\BillPaymentClientInterface;
use App\Contracts\IdentityVerificationClientInterface;
use App\Contracts\MmoClientInterface;
use App\Contracts\SmsClientInterface;
use App\Services\IdentityVerificationSettingsService;
use App\Services\AfricasTalkingSmsClient;
use App\Services\BillPaymentSettingsService;
use App\Services\FailoverSmsClient;
use App\Services\LogSmsClient;
use App\Services\MockBillPaymentClient;
use App\Services\MmoProviderSettingService;
use App\Services\MockMmoClient;
use App\Services\MonnifyWalletProvisioning;
use App\Services\SystemSettingService;
use App\Services\TermiiSmsClient;
use App\Services\VTPassClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MmoClientInterface::class, function () {
            if (!Schema::hasTable('mmo_provider_settings')) {
                return match (config('services.mmo.driver', 'mock')) {
                    'mock' => new MockMmoClient(),
                    default => new MockMmoClient(),
                };
            }

            return app(MmoProviderSettingService::class)->resolveActiveClient();
        });

        $this->app->bind(SmsClientInterface::class, function () {
            if ($this->app->environment('testing')) {
                return new LogSmsClient();
            }

            $driver = config('services.sms.driver', 'termii');

            if ($driver === 'log') {
                return new LogSmsClient();
            }

            $clients = [];

            if (in_array($driver, ['termii', 'failover'], true)) {
                $clients[] = new TermiiSmsClient();
            }

            if (in_array($driver, ['africastalking', 'failover'], true)) {
                $clients[] = new AfricasTalkingSmsClient(
                    (string) config('services.africastalking.username', ''),
                    (string) config('services.africastalking.api_key', ''),
                    (string) config('services.africastalking.sender_id', ''),
                    (string) config('services.africastalking.environment', 'sandbox'),
                );
            }

            return count($clients) > 1
                ? new FailoverSmsClient($clients)
                : ($clients[0] ?? new LogSmsClient());
        });

        $this->app->bind(BillPaymentClientInterface::class, function () {
            if ($this->app->environment('testing')) {
                return new MockBillPaymentClient();
            }

            if (!Schema::hasTable('system_settings')) {
                return new MockBillPaymentClient();
            }

            return app(BillPaymentSettingsService::class)->makeClient();
        });

        $this->app->singleton(MonnifyWalletProvisioning::class, function () {
            return new MonnifyWalletProvisioning(app(MmoClientInterface::class));
        });

        $this->app->bind(IdentityVerificationClientInterface::class, function () {
            return app(IdentityVerificationSettingsService::class)->makeYouverifyClient();
        });
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Transaction::observe(TransactionObserver::class);

        rescue(function (): void {
            app(SystemSettingService::class)->applyNotificationConfig();
        }, report: true);

        rescue(function (): void {
            if (Schema::hasTable('mmo_provider_settings')) {
                app(MmoProviderSettingService::class)->getProviderSettings();
            }
        }, report: true);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('pin', fn (Request $request) => Limit::perMinute(5)->by(auth()->id() ?? $request->ip()));
        RateLimiter::for('registration', fn (Request $request) => Limit::perHour(3)->by($request->ip()));
        RateLimiter::for('webhook', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('ussd', fn (Request $request) => Limit::perMinute(20)->by($request->input('sessionId', $request->ip())));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

        rescue(function (): void {
            if (Schema::hasTable('site_settings')) {
                View::share('siteSettings', SiteSetting::getSiteSettings());
                return;
            }

            View::share('siteSettings', SiteSetting::defaultSettings());
        }, report: true);
    }
}
