<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Agent;
use App\Models\AjoOwner;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    protected $accounts = [];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'ajo_owner', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // Create test customer
        $customer = User::create([
            'phone_number' => '08012345001',
            'full_name' => 'Test Customer',
            'pin_hash' => Hash::make('123456', ['rounds' => 12]),
            'status' => 'active',
            'kyc_level' => 1,
        ]);
        $customer->assignRole('customer');
        $this->createWallet($customer, 'customer');
        $this->accounts[] = ['role' => 'Customer', 'phone' => '08012345001', 'pin' => '123456'];

        // Create second test customer for transfers
        $recipientCustomer = User::create([
            'phone_number' => '08012345006',
            'full_name' => 'Recipient Customer',
            'pin_hash' => Hash::make('123456', ['rounds' => 12]),
            'status' => 'active',
            'kyc_level' => 1,
        ]);
        $recipientCustomer->assignRole('customer');
        $this->createWallet($recipientCustomer, 'customer');
        $this->accounts[] = ['role' => 'Customer 2', 'phone' => '08012345006', 'pin' => '123456'];

        // Create test agent
        $agentUser = User::create([
            'phone_number' => '08012345002',
            'full_name' => 'Test Agent',
            'pin_hash' => Hash::make('123456', ['rounds' => 12]),
            'status' => 'active',
            'kyc_level' => 2,
        ]);
        $agentUser->assignRole('agent');
        Agent::create([
            'user_id' => $agentUser->id,
            'business_name' => 'Test Agent Business',
            'business_address' => '123 Main Street, Lagos',
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
            'lga' => 'Lagos Island',
            'state' => 'Lagos',
            'float_balance' => 50000.00,
            'max_float' => 100000.00,
            'commission_rate' => 1.5,
            'total_earnings' => 0.00,
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $this->createWallet($agentUser, 'agent');
        $this->accounts[] = ['role' => 'Agent', 'phone' => '08012345002', 'pin' => '123456'];

        // Create pending agent for admin approval flow
        $pendingAgentUser = User::create([
            'phone_number' => '08012345007',
            'full_name' => 'Pending Agent',
            'pin_hash' => Hash::make('123456', ['rounds' => 12]),
            'status' => 'active',
            'kyc_level' => 2,
        ]);
        $pendingAgentUser->assignRole('agent');
        Agent::create([
            'user_id' => $pendingAgentUser->id,
            'business_name' => 'Pending Agent Outlet',
            'business_address' => '18 Broad Street, Lagos',
            'gps_latitude' => 6.4550,
            'gps_longitude' => 3.3841,
            'lga' => 'Eti-Osa',
            'state' => 'Lagos',
            'float_balance' => 5000.00,
            'max_float' => 50000.00,
            'commission_rate' => 1.50,
            'total_earnings' => 0.00,
            'status' => 'pending',
        ]);
        $this->createWallet($pendingAgentUser, 'agent');
        $this->accounts[] = ['role' => 'Pending Agent', 'phone' => '08012345007', 'pin' => '123456'];

        // Create test ajo owner
        $ajoOwnerUser = User::create([
            'phone_number' => '08012345003',
            'full_name' => 'Test Ajo Owner',
            'pin_hash' => Hash::make('123456', ['rounds' => 12]),
            'status' => 'active',
            'kyc_level' => 2,
        ]);
        $ajoOwnerUser->assignRole('ajo_owner');
        $ajoOwner = AjoOwner::create([
            'user_id' => $ajoOwnerUser->id,
            'business_name' => 'Test Ajo Group',
            'status' => 'active',
        ]);
        $this->accounts[] = ['role' => 'Ajo Owner', 'phone' => '08012345003', 'pin' => '123456'];

        // Create agent for ajo owner
        $ajoAgentUser = User::create([
            'phone_number' => '08012345004',
            'full_name' => 'Test Ajo Agent',
            'pin_hash' => Hash::make('123456', ['rounds' => 12]),
            'status' => 'active',
            'kyc_level' => 2,
        ]);
        $ajoAgentUser->assignRole('agent');
        Agent::create([
            'user_id' => $ajoAgentUser->id,
            'ajo_owner_id' => $ajoOwner->id,
            'business_name' => 'Ajo Agent Business',
            'business_address' => '456 Market Road, Ibadan',
            'gps_latitude' => 7.3775,
            'gps_longitude' => 3.9470,
            'lga' => 'Ibadan North',
            'state' => 'Oyo',
            'float_balance' => 30000.00,
            'max_float' => 75000.00,
            'commission_rate' => 1.25,
            'total_earnings' => 0.00,
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $this->createWallet($ajoAgentUser, 'agent');
        $this->accounts[] = ['role' => 'Ajo Agent', 'phone' => '08012345004', 'pin' => '123456'];

        // Create test admin
        $admin = User::create([
            'phone_number' => '08012345005',
            'full_name' => 'Test Admin',
            'pin_hash' => Hash::make('123456', ['rounds' => 12]),
            'status' => 'active',
            'kyc_level' => 3,
        ]);
        $admin->assignRole('admin');
        $this->accounts[] = ['role' => 'Admin', 'phone' => '08012345005', 'pin' => '123456'];

        // Create test super admin
        $superAdmin = User::create([
            'phone_number' => '08012345000',
            'full_name' => 'Test Super Admin',
            'pin_hash' => Hash::make('123456', ['rounds' => 12]),
            'status' => 'active',
            'kyc_level' => 3,
        ]);
        $superAdmin->assignRole('super_admin');
        $this->accounts[] = ['role' => 'Super Admin', 'phone' => '08012345000', 'pin' => '123456'];

        // Create test transactions
        $this->createTestTransactions(
            $customer,
            $recipientCustomer,
            $agentUser,
            $customer->wallets()->where('wallet_type', 'customer')->first(),
            $recipientCustomer->wallets()->where('wallet_type', 'customer')->first()
        );

        // Create test Ajo groups
        $this->createTestAjoGroups($ajoOwner, Agent::where('user_id', $ajoAgentUser->id)->first());

        // Create test KYC documents
        $this->createTestKycDocuments($customer, $agentUser, $ajoOwnerUser);

        // Output credentials
        $this->command->info('Test accounts created successfully:');
        $this->command->table(['Role', 'Phone Number', 'PIN'], $this->accounts);
    }

    protected function createTestTransactions(User $customer, User $recipientCustomer, User $agentUser, Wallet $customerWallet, Wallet $recipientWallet): void
    {
        // Test transfer
        \App\Models\Transaction::create([
            'reference' => 'TXN_TEST_001',
            'transaction_type' => 'transfer',
            'amount' => 10000.00,
            'fee' => 50.00,
            'status' => 'completed',
            'from_wallet_id' => $customerWallet->id,
            'to_wallet_id' => $recipientWallet->id,
            'recipient_phone' => $recipientCustomer->phone_number,
            'description' => 'Test transfer from customer to recipient customer',
            'mmo_partner' => 'internal',
            'mmo_transaction_id' => 'MMO-SEED-001',
            'completed_at' => now(),
        ]);

        // Test cash in
        \App\Models\Transaction::create([
            'reference' => 'TXN_TEST_002',
            'transaction_type' => 'deposit',
            'amount' => 20000.00,
            'commission' => 300.00,
            'status' => 'completed',
            'to_wallet_id' => $customerWallet->id,
            'agent_id' => $agentUser->id,
            'description' => 'Test cash in at agent',
            'mmo_partner' => 'internal',
            'completed_at' => now(),
        ]);

        // Test cash out
        \App\Models\Transaction::create([
            'reference' => 'TXN_TEST_003',
            'transaction_type' => 'withdrawal',
            'amount' => 5000.00,
            'commission' => 75.00,
            'status' => 'completed',
            'from_wallet_id' => $customerWallet->id,
            'agent_id' => $agentUser->id,
            'description' => 'Test cash out at agent',
            'mmo_partner' => 'internal',
            'completed_at' => now(),
        ]);

        // Failed transaction alert seed
        \App\Models\Transaction::create([
            'reference' => 'TXN_TEST_004',
            'transaction_type' => 'transfer',
            'amount' => 2500.00,
            'fee' => 12.50,
            'status' => 'failed',
            'from_wallet_id' => $customerWallet->id,
            'to_wallet_id' => $recipientWallet->id,
            'recipient_phone' => $recipientCustomer->phone_number,
            'description' => 'Seeded failed transfer for admin alerts',
            'mmo_partner' => 'internal',
            'metadata' => ['error' => 'Seeded admin alert transaction'],
        ]);
    }

    protected function createTestAjoGroups(AjoOwner $ajoOwner, Agent $agent): void
    {
        // Test daily group
        $group = \App\Models\AjoGroup::create([
            'ajo_owner_id' => $ajoOwner->id,
            'name' => 'Market Women Daily Savings',
            'contribution_amount' => 2000.00,
            'frequency' => 'daily',
            'members_count' => 12,
            'managing_agent_id' => $agent->id,
            'status' => 'active',
            'start_date' => now()->subDays(30),
        ]);

        // Test weekly group
        \App\Models\AjoGroup::create([
            'ajo_owner_id' => $ajoOwner->id,
            'name' => 'Surulere Mechanics Weekly',
            'contribution_amount' => 10000.00,
            'frequency' => 'weekly',
            'members_count' => 8,
            'managing_agent_id' => $agent->id,
            'status' => 'pending',
            'start_date' => now()->addDays(7),
        ]);
    }

    protected function createTestKycDocuments(User $customer, User $agent, User $ajoOwner): void
    {
        // Pending KYC for customer
        \App\Models\KycDocument::create([
            'user_id' => $customer->id,
            'document_type' => 'National ID (NIN)',
            'document_url' => '/storage/kyc/test_nin.jpg',
            'verification_status' => 'pending',
        ]);

        // Verified KYC for agent
        \App\Models\KycDocument::create([
            'user_id' => $agent->id,
            'document_type' => 'Driver\'s License',
            'document_url' => '/storage/kyc/test_license.jpg',
            'verification_status' => 'verified',
            'verified_at' => now()->subDays(5),
        ]);

        // Rejected KYC for ajo owner
        \App\Models\KycDocument::create([
            'user_id' => $ajoOwner->id,
            'document_type' => 'Utility Bill',
            'document_url' => '/storage/kyc/test_bill.jpg',
            'verification_status' => 'rejected',
        ]);
    }

    protected function createWallet(User $user, string $type): void
    {
        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => $type,
            'balance' => 50000.00,
            'available_balance' => 50000.00,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000.00,
            'single_txn_limit' => 200000.00,
            'mmo_partner' => 'test_mmo',
            'mmo_wallet_id' => uniqid(),
        ]);
    }
}
