<?php

namespace Tests\Feature;

use App\Contracts\IdentityVerificationClientInterface;
use App\Livewire\Agent\UpgradeCustomer;
use App\Livewire\Agent\VerifyNin;
use App\Models\Agent;
use App\Models\KycDocument;
use App\Models\User;
use App\Services\MockIdentityVerificationClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgentStandaloneNinVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $agentUser;
    protected Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        foreach (['customer', 'agent', 'admin', 'super_admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->agentUser = User::create([
            'phone_number' => '08110000001',
            'full_name' => 'Test Agent',
            'kyc_level' => 2,
            'status' => 'active',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
        ]);
        $this->agentUser->assignRole('agent');

        $this->agent = Agent::create([
            'user_id' => $this->agentUser->id,
            'business_name' => 'Test Agent Shop',
            'business_address' => '123 Test Street',
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
            'lga' => 'Ikeja',
            'state' => 'Lagos',
            'float_balance' => 100000,
            'max_float' => 500000,
            'commission_rate' => 2.5,
            'total_earnings' => 0,
            'status' => 'active',
        ]);
    }

    protected function createTier1Customer(string $phone, string $name): User
    {
        $normalized = \App\Helpers\PhoneNumberHelper::normalize($phone);

        $customer = User::create([
            'phone_number' => $normalized,
            'full_name' => $name,
            'kyc_level' => 1,
            'status' => 'active',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
        ]);
        $customer->assignRole('customer');

        app(\App\Services\WalletService::class)->createTierWallet($customer, 1);

        return $customer;
    }

    public function test_standalone_nin_verification_sets_timestamp_but_does_not_complete_tier2(): void
    {
        $this->app->instance(IdentityVerificationClientInterface::class, new MockIdentityVerificationClient());

        $customer = $this->createTier1Customer('08120000001', 'NIN Only Customer');

        $this->actingAs($this->agentUser);

        Livewire::test(VerifyNin::class)
            ->set('searchPhone', '08120000001')
            ->call('searchCustomer')
            ->assertSet('step', 2)
            ->set('nin', '12345678901')
            ->set('fullName', 'NIN Only Customer')
            ->call('verifyNin')
            ->assertSet('verificationStatus', 'verified')
            ->assertSet('step', 3)
            ->set('agentPin', '123456')
            ->call('verifyAgentPin')
            ->assertSet('step', 4)
            ->assertSet('tier2Completed', false);

        $customer->refresh();
        $this->assertNotNull($customer->nin_verified_at);
        $this->assertNull($customer->bvn_verified_at);
        $this->assertNull($customer->next_of_kin_submitted_at);
        $this->assertSame(1, (int) $customer->kyc_level);

        $this->assertDatabaseHas('kyc_documents', [
            'user_id' => $customer->id,
            'document_type' => 'nin',
            'verification_status' => 'verified',
        ]);
    }

    public function test_name_mismatch_is_flagged_and_not_silently_accepted(): void
    {
        $mock = new MockIdentityVerificationClient();
        $this->app->instance(IdentityVerificationClientInterface::class, $mock);

        $customer = $this->createTier1Customer('08120000002', 'John Doe');

        $this->actingAs($this->agentUser);

        Livewire::test(VerifyNin::class)
            ->set('searchPhone', '08120000002')
            ->call('searchCustomer')
            ->set('nin', '12345678901')
            ->set('fullName', 'Wrong Name')
            ->call('verifyNin')
            ->assertSet('verificationStatus', 'failed');

        $customer->refresh();
        $this->assertNull($customer->nin_verified_at);
        $this->assertSame(1, (int) $customer->kyc_level);
    }

    public function test_re_verifying_already_verified_nin_does_not_duplicate_or_re_trigger_tier2(): void
    {
        $this->app->instance(IdentityVerificationClientInterface::class, new MockIdentityVerificationClient());

        $customer = $this->createTier1Customer('08120000003', 'Already Verified');
        $customer->update([
            'nin' => '12345678901',
            'nin_verified_at' => now(),
        ]);

        KycDocument::create([
            'user_id' => $customer->id,
            'document_type' => 'nin',
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        $this->actingAs($this->agentUser);

        Livewire::test(VerifyNin::class)
            ->set('searchPhone', '08120000003')
            ->call('searchCustomer')
            ->set('nin', '12345678901')
            ->set('fullName', 'Already Verified')
            ->call('verifyNin')
            ->assertSet('verificationStatus', 'already_verified')
            ->assertSet('step', 3)
            ->set('agentPin', '123456')
            ->call('verifyAgentPin')
            ->assertSet('step', 4)
            ->assertSet('tier2Completed', false);

        $this->assertSame(1, (int) KycDocument::where('user_id', $customer->id)->where('document_type', 'nin')->count());
    }

    public function test_two_visit_path_nin_then_bvn_next_of_kin_completes_tier2(): void
    {
        $this->app->instance(IdentityVerificationClientInterface::class, new MockIdentityVerificationClient());

        $customer = $this->createTier1Customer('08120000004', 'Two Visit Customer');

        $this->actingAs($this->agentUser);

        // Visit 1: standalone NIN verification
        Livewire::test(VerifyNin::class)
            ->set('searchPhone', '08120000004')
            ->call('searchCustomer')
            ->set('nin', '12345678901')
            ->set('fullName', 'Two Visit Customer')
            ->call('verifyNin')
            ->set('agentPin', '123456')
            ->call('verifyAgentPin');

        $customer->refresh();
        $this->assertSame(1, (int) $customer->kyc_level);
        $this->assertNotNull($customer->nin_verified_at);

        // Visit 2: submit remaining BVN + Next of Kin via upgrade flow
        Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08120000004')
            ->call('searchCustomer')
            ->call('selectTier', 2)
            ->assertSet('step', 3)
            ->set('bvn', '98765432101')
            ->set('nextOfKinName', 'Next Kin')
            ->set('nextOfKinRelationship', 'Spouse')
            ->set('nextOfKinPhone', '08080000001')
            ->set('ninSlip', UploadedFile::fake()->image('nin.jpg'))
            ->set('bvnSlip', UploadedFile::fake()->image('bvn.jpg'))
            ->set('livenessCapture', UploadedFile::fake()->image('liveness.jpg'))
            ->call('submitTierDetails')
            ->assertSet('step', 4)
            ->set('customerPin', '123456')
            ->call('verifyCustomerPin')
            ->assertSet('step', 5)
            ->assertDispatched('notify-success');

        $customer->refresh();
        $this->assertSame(2, (int) $customer->kyc_level);
        $this->assertNotNull($customer->nin_verified_at);
        $this->assertNotNull($customer->bvn_verified_at);
        $this->assertNotNull($customer->next_of_kin_submitted_at);
    }

    public function test_upgrade_customer_flow_skips_already_verified_nin(): void
    {
        $customer = $this->createTier1Customer('08120000005', 'Skip NIN Customer');
        $customer->update([
            'nin' => '12345678901',
            'nin_verified_at' => now(),
        ]);

        $this->actingAs($this->agentUser);

        Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08120000005')
            ->call('searchCustomer')
            ->call('selectTier', 2)
            ->assertSet('step', 3)
            ->set('bvn', '98765432101')
            ->set('nextOfKinName', 'Next Kin')
            ->set('nextOfKinRelationship', 'Spouse')
            ->set('nextOfKinPhone', '08080000001')
            ->set('ninSlip', UploadedFile::fake()->image('nin.jpg'))
            ->set('bvnSlip', UploadedFile::fake()->image('bvn.jpg'))
            ->set('livenessCapture', UploadedFile::fake()->image('liveness.jpg'))
            ->call('submitTierDetails')
            ->assertSet('step', 4)
            ->set('customerPin', '123456')
            ->call('verifyCustomerPin')
            ->assertSet('step', 5);

        $customer->refresh();
        $this->assertSame(2, (int) $customer->kyc_level);
    }

    public function test_single_visit_upgrade_customer_still_completes_tier2(): void
    {
        $customer = $this->createTier1Customer('08120000006', 'Single Visit Customer');

        $this->actingAs($this->agentUser);

        Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08120000006')
            ->call('searchCustomer')
            ->call('selectTier', 2)
            ->set('nin', '12345678901')
            ->set('bvn', '98765432101')
            ->set('nextOfKinName', 'Next Kin')
            ->set('nextOfKinRelationship', 'Spouse')
            ->set('nextOfKinPhone', '08080000001')
            ->set('ninSlip', UploadedFile::fake()->image('nin.jpg'))
            ->set('bvnSlip', UploadedFile::fake()->image('bvn.jpg'))
            ->set('livenessCapture', UploadedFile::fake()->image('liveness.jpg'))
            ->call('submitTierDetails')
            ->set('customerPin', '123456')
            ->call('verifyCustomerPin')
            ->assertSet('step', 5);

        $customer->refresh();
        $this->assertSame(2, (int) $customer->kyc_level);
        $this->assertNotNull($customer->nin_verified_at);
        $this->assertNotNull($customer->bvn_verified_at);
        $this->assertNotNull($customer->next_of_kin_submitted_at);
    }

    public function test_admin_kyc_queue_shows_three_part_progress(): void
    {
        $admin = User::create([
            'phone_number' => '08130000001',
            'full_name' => 'Admin User',
            'kyc_level' => 2,
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $customer = $this->createTier1Customer('08120000007', 'Progress Customer');
        $customer->update([
            'nin_verified_at' => now(),
            'next_of_kin_submitted_at' => now(),
        ]);

        $document = KycDocument::create([
            'user_id' => $customer->id,
            'document_type' => 'bvn_slip',
            'document_url' => '/storage/kyc/bvn.jpg',
            'verification_status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\KycQueue::class)
            ->call('viewVerificationDetails', $document->id)
            ->assertSee('Tier 2 Progress')
            ->assertSee('NIN')
            ->assertSee('BVN')
            ->assertSee('Next of Kin');
    }
}
