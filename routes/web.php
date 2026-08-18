<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\MonnifyWebhookController;
use App\Http\Controllers\UssdController;
use App\Http\Controllers\HealthController;

// Auth Routes
use App\Livewire\Auth\Register;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\VerifyOtp;
use App\Livewire\Auth\ChangePassword;
use App\Livewire\Auth\ForgotPassword;

// Customer Routes
use App\Livewire\Customer\Dashboard as CustomerDashboard;
use App\Livewire\Customer\SendMoney as CustomerSendMoney;
use App\Livewire\Customer\History as CustomerHistory;
use App\Livewire\Customer\Profile as CustomerProfile;
use App\Livewire\Customer\KycUpgrade as CustomerKycUpgrade;
use App\Livewire\Customer\KycAddress as CustomerKycAddress;
use App\Livewire\Customer\BuyAirtime as CustomerBuyAirtime;
use App\Livewire\Customer\MyAjo as CustomerMyAjo;
use App\Livewire\Customer\MyAjoDetail as CustomerMyAjoDetail;
use App\Livewire\Customer\AddMoney;
use App\Livewire\Customer\PayBills;
use App\Livewire\Customer\GetLoan;
use App\Livewire\Customer\PersonalInfo;
use App\Livewire\Customer\TransactionLimits;
use App\Livewire\Customer\NotificationsSettings as CustomerNotificationsSettings;
use App\Livewire\Customer\Language as CustomerLanguage;
use App\Livewire\Customer\HelpSupport as CustomerHelpSupport;

// Agent Routes
use App\Livewire\Agent\Dashboard as AgentDashboard;
use App\Livewire\Agent\CashIn as AgentCashIn;
use App\Livewire\Agent\CashOut as AgentCashOut;
use App\Livewire\Agent\Earnings as AgentEarnings;
use App\Livewire\Agent\Profile as AgentProfile;
use App\Livewire\Agent\AjoCollection as AgentAjoCollection;
use App\Livewire\Agent\RequestTopUp as AgentRequestTopUp;
use App\Livewire\Agent\SettleFloat as AgentSettleFloat;
use App\Livewire\Agent\CreateCustomer as AgentCreateCustomer;
use App\Livewire\Agent\UpgradeCustomer as AgentUpgradeCustomer;
use App\Livewire\Agent\VerifyNin as AgentVerifyNin;
use App\Livewire\Agent\Customers as AgentCustomers;
use App\Livewire\Agent\Transactions as AgentTransactions;
use App\Livewire\Agent\NotificationSettings as AgentNotificationSettings;
use App\Livewire\Agent\KycSubmissions as AgentKycSubmissions;

// Admin Routes
use App\Livewire\Admin\Overview as AdminOverview;
use App\Livewire\Admin\Users as AdminUsers;
use App\Livewire\Admin\Agents as AdminAgents;
use App\Livewire\Admin\AjoOwners as AdminAjoOwners;
use App\Livewire\Admin\Transactions as AdminTransactions;
use App\Livewire\Admin\AjoGroups as AdminAjoGroups;
use App\Livewire\Admin\KycQueue as AdminKycQueue;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\SiteSettings;
use App\Livewire\Admin\Disbursements as AdminDisbursements;
use App\Livewire\Admin\FloatManagement as AdminFloatManagement;
use App\Livewire\Admin\Liquidity as AdminLiquidity;
use App\Livewire\Admin\AjoPayoutQueue as AdminAjoPayoutQueue;

// Ajo Owner Routes
use App\Livewire\AjoOwner\Dashboard as AjoOwnerDashboard;
use App\Livewire\AjoOwner\Groups as AjoOwnerGroups;
use App\Livewire\AjoOwner\GroupCreate as AjoOwnerGroupCreate;
use App\Livewire\AjoOwner\GroupDetail as AjoOwnerGroupDetail;
use App\Livewire\AjoOwner\Agents as AjoOwnerAgents;
use App\Livewire\AjoOwner\Payouts as AjoOwnerPayouts;
use App\Livewire\AjoOwner\Profile as AjoOwnerProfile;
use App\Livewire\AjoOwner\BusinessInfo as AjoOwnerBusinessInfo;
use App\Livewire\AjoOwner\PayoutSettings as AjoOwnerPayoutSettings;
use App\Livewire\AjoOwner\NotificationsSettings as AjoOwnerNotificationsSettings;
use App\Livewire\AjoOwner\HelpSupport as AjoOwnerHelpSupport;

// ─── Root ────────────────────────────────────────────────────
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return redirect()->route('admin.overview');
        } elseif ($user->hasRole('ajo_owner')) {
            return redirect()->route('ajo-owner.dashboard');
        } elseif ($user->hasRole('agent')) {
            if ($user->agent()->exists()) {
                return redirect()->route('ajo-agent.dashboard');
            }
            return redirect()->route('agent.dashboard');
        } else {
            return redirect()->route('customer.dashboard');
        }
    }
    return view('landing');
})->name('home');

Route::get('/api/health', HealthController::class)->name('api.health');

Route::get('/style-guide', function () {
    if (app()->environment('production')) {
        abort(404);
    }

    return view('style-guide');
});

Route::post('/webhooks/monnify', MonnifyWebhookController::class)->name('webhooks.monnify');
Route::get('/payment/callback', [\App\Http\Controllers\MonnifyPaymentCallback::class, 'handleRedirect'])->name('payment.callback');
Route::post('/webhooks/opay', fn () => response()->json(['message' => 'OPay webhook integration pending.'], 501))->name('webhooks.opay');
Route::post('/webhooks/palmpay', fn () => response()->json(['message' => 'PalmPay webhook integration pending.'], 501))->name('webhooks.palmpay');

// USSD Callback
Route::post('/ussd/callback', UssdController::class)->name('ussd.callback');

// Auth Routes (Guest)
Route::middleware(['guest', 'throttle:registration'])->group(function () {
    Route::get('/register', Register::class)->name('register');
});

Route::middleware(['guest', 'throttle:login'])->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/verify-otp', VerifyOtp::class)->name('verify-otp');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.forgot');
});

Route::middleware(['auth', 'throttle:pin'])->group(function () {
    Route::get('/change-password', ChangePassword::class)->name('password.change');
});

// Logout
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Customer Routes
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/dashboard', CustomerDashboard::class)->name('customer.dashboard');
    Route::get('/send-money', CustomerSendMoney::class)->name('customer.send-money');
    Route::get('/history', CustomerHistory::class)->name('customer.history');
    Route::get('/buy-airtime', CustomerBuyAirtime::class)->name('customer.buy-airtime');
    Route::get('/my-ajo', CustomerMyAjo::class)->name('customer.my-ajo');
    Route::get('/my-ajo/{id}', CustomerMyAjoDetail::class)->name('customer.my-ajo-detail');
    Route::get('/profile', CustomerProfile::class)->name('customer.profile');
    Route::get('/kyc-upgrade', CustomerKycUpgrade::class)->name('customer.kyc-upgrade');
    Route::get('/kyc-address', CustomerKycAddress::class)->name('customer.kyc-address');
    Route::get('/add-money', AddMoney::class)->name('customer.add-money');
    Route::get('/pay-bills', PayBills::class)->name('customer.pay-bills');
    Route::get('/get-loan', GetLoan::class)->name('customer.get-loan');
    Route::get('/profile/personal-info', PersonalInfo::class)->name('customer.personal-info');
    Route::get('/profile/transaction-limits', TransactionLimits::class)->name('customer.transaction-limits');
    Route::get('/profile/notifications', CustomerNotificationsSettings::class)->name('customer.notifications');
    Route::get('/profile/language', CustomerLanguage::class)->name('customer.language');
    Route::get('/profile/help-support', CustomerHelpSupport::class)->name('customer.help-support');
});

// Ajo Owner application (auth required, no role gate — handled by component)
Route::get('/become-ajo-owner', \App\Livewire\Public\BecomeAjoOwner::class)
    ->middleware('auth')
    ->name('public.become-ajo-owner');

// Ajo Owner end-to-end signup (no auth required — creates account + application)
Route::get('/ajo-owner/signup', \App\Livewire\Public\AjoOwnerSignup::class)
    ->name('ajo-owner.signup');

// Agent Routes
Route::prefix('agent')->middleware(['auth', 'role:agent'])->group(function () {
    Route::get('/dashboard', AgentDashboard::class)->name('agent.dashboard');
    Route::get('/cash-in', AgentCashIn::class)->name('agent.cash-in');
    Route::get('/cash-out', AgentCashOut::class)->name('agent.cash-out');
    Route::get('/ajo-collection', AgentAjoCollection::class)->name('agent.ajo-collection');
    Route::get('/earnings', AgentEarnings::class)->name('agent.earnings');
    Route::get('/request-topup', AgentRequestTopUp::class)->name('agent.request-topup');
    Route::get('/settle-float', AgentSettleFloat::class)->name('agent.settle-float');
    Route::get('/profile', AgentProfile::class)->name('agent.profile');
    Route::get('/transactions', AgentTransactions::class)->name('agent.transactions');
    Route::get('/notifications', AgentNotificationSettings::class)->name('agent.notifications');
    Route::get('/kyc-submissions', AgentKycSubmissions::class)->name('agent.kyc-submissions');
    Route::get('/create-customer', AgentCreateCustomer::class)->name('agent.create-customer');
    Route::get('/upgrade-customer', AgentUpgradeCustomer::class)->name('agent.upgrade-customer');
    Route::get('/verify-nin', AgentVerifyNin::class)->name('agent.verify-nin');
    Route::get('/customers', AgentCustomers::class)->name('agent.customers');
});

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'role:admin|super_admin'])->group(function () {
    Route::get('/overview', AdminOverview::class)->name('admin.overview');
    Route::get('/users', AdminUsers::class)->name('admin.users');
    Route::get('/ajo-owners', AdminAjoOwners::class)->name('admin.ajo-owners');
    Route::get('/agents', AdminAgents::class)->name('admin.agents');
    Route::get('/transactions', AdminTransactions::class)->name('admin.transactions');
    Route::get('/ajo-groups', AdminAjoGroups::class)->name('admin.ajo-groups');
    Route::get('/kyc-queue', AdminKycQueue::class)->name('admin.kyc-queue');
    Route::get('/disbursements', AdminDisbursements::class)->name('admin.disbursements');
    Route::get('/float-management', AdminFloatManagement::class)->name('admin.float-management');
    Route::get('/liquidity', AdminLiquidity::class)->name('admin.liquidity');
    Route::get('/ajo-payout-queue', AdminAjoPayoutQueue::class)->name('admin.ajo-payout-queue');
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/settings', AdminSettings::class)->name('admin.settings');
        Route::get('/site-settings', SiteSettings::class)->name('admin.site-settings');
    });
});

// Ajo Owner Routes
Route::prefix('ajo-owner')->middleware(['auth', 'role:ajo_owner'])->group(function () {
    Route::get('/dashboard', AjoOwnerDashboard::class)->name('ajo-owner.dashboard');
    Route::get('/groups', AjoOwnerGroups::class)->name('ajo-owner.groups');
    Route::get('/groups/create', AjoOwnerGroupCreate::class)->name('ajo-owner.groups.create');
    Route::get('/groups/{id}', AjoOwnerGroupDetail::class)->name('ajo-owner.groups.detail');
    Route::get('/kyc', \App\Livewire\AjoOwner\Kyc::class)->name('ajo-owner.kyc');
    Route::get('/agents/{id}', \App\Livewire\AjoOwner\AgentDetail::class)->name('ajo-owner.agents.detail');
    Route::get('/agents', AjoOwnerAgents::class)->name('ajo-owner.agents');
    Route::get('/add-fund', \App\Livewire\AjoOwner\AddFund::class)->name('ajo-owner.add-fund');
    Route::get('/send-fund', \App\Livewire\AjoOwner\SendFund::class)->name('ajo-owner.send-fund');
    Route::get('/pay-bills', \App\Livewire\AjoOwner\PayBills::class)->name('ajo-owner.pay-bills');
    Route::get('/payouts', AjoOwnerPayouts::class)->name('ajo-owner.payouts');
    Route::get('/profile', AjoOwnerProfile::class)->name('ajo-owner.profile');
    Route::get('/profile/business-info', AjoOwnerBusinessInfo::class)->name('ajo-owner.profile.business-info');
    Route::get('/profile/payout-settings', AjoOwnerPayoutSettings::class)->name('ajo-owner.profile.payout-settings');
    Route::get('/profile/notifications', AjoOwnerNotificationsSettings::class)->name('ajo-owner.profile.notifications');
    Route::get('/profile/help-support', AjoOwnerHelpSupport::class)->name('ajo-owner.profile.help-support');
});

// Ajo Agent Routes (field agents created by Ajo Owners)
Route::prefix('ajo-agent')->middleware(['auth', 'role:agent'])->group(function () {
    Route::get('/dashboard', \App\Livewire\AjoAgent\Dashboard::class)->name('ajo-agent.dashboard');
    Route::get('/profile', \App\Livewire\AjoAgent\Profile::class)->name('ajo-agent.profile');
    Route::get('/transactions', \App\Livewire\AjoAgent\Transactions::class)->name('ajo-agent.transactions');
    Route::get('/collect', \App\Livewire\AjoAgent\Collect::class)->name('ajo-agent.collect');
    Route::get('/groups', \App\Livewire\AjoAgent\Groups::class)->name('ajo-agent.groups');
    Route::get('/send-money', \App\Livewire\AjoAgent\SendMoney::class)->name('ajo-agent.send-money');
    Route::get('/pay-bills', \App\Livewire\AjoAgent\PayBills::class)->name('ajo-agent.pay-bills');
    Route::get('/kyc-upgrade', \App\Livewire\AjoAgent\KycUpgrade::class)->name('ajo-agent.kyc-upgrade');
    Route::get('/create-member', \App\Livewire\AjoAgent\CreateMember::class)->name('ajo-agent.create-member');
});
