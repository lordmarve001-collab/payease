<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionObserverTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'full_name' => 'Test User',
            'phone_number' => '8031111111',
            'pin_hash' => Hash::make('123456'),
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'wallet_type' => 'customer',
            'balance' => 10000.00,
            'available_balance' => 10000.00,
            'currency' => 'NGN',
            'status' => 'active',
            'mmo_partner' => 'monnify',
        ]);
    }

    #[Test]
    public function creates_audit_log_on_transaction_creation(): void
    {
        $transaction = Transaction::create([
            'reference' => 'TST-' . Str::random(10),
            'transaction_type' => 'transfer',
            'amount' => 500.00,
            'status' => 'pending',
            'from_wallet_id' => $this->wallet->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_created',
            'entity_type' => 'transaction',
            'entity_id' => $transaction->id,
        ]);

        $log = AuditLog::where('entity_id', $transaction->id)->first();
        $this->assertEquals('pending', $log->new_values['status']);
    }

    #[Test]
    public function creates_audit_log_on_status_change(): void
    {
        $transaction = Transaction::create([
            'reference' => 'TST-' . Str::random(10),
            'transaction_type' => 'transfer',
            'amount' => 500.00,
            'status' => 'pending',
            'from_wallet_id' => $this->wallet->id,
        ]);

        $transaction->update(['status' => 'completed', 'completed_at' => now()]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_status_changed',
            'entity_type' => 'transaction',
            'entity_id' => $transaction->id,
        ]);

        $statusLogs = AuditLog::where('entity_id', $transaction->id)
            ->where('action', 'transaction_status_changed')
            ->get();

        $this->assertCount(1, $statusLogs);
        $this->assertEquals('pending', $statusLogs[0]->old_values['status']);
        $this->assertEquals('completed', $statusLogs[0]->new_values['status']);
    }

    #[Test]
    public function does_not_log_when_non_status_field_is_updated(): void
    {
        $transaction = Transaction::create([
            'reference' => 'TST-' . Str::random(10),
            'transaction_type' => 'transfer',
            'amount' => 500.00,
            'status' => 'pending',
            'from_wallet_id' => $this->wallet->id,
        ]);

        $transaction->update(['description' => 'test description']);

        $statusLogs = AuditLog::where('entity_id', $transaction->id)
            ->where('action', 'transaction_status_changed')
            ->get();

        $this->assertCount(0, $statusLogs);
    }

    #[Test]
    public function logs_multiple_status_changes(): void
    {
        $transaction = Transaction::create([
            'reference' => 'TST-' . Str::random(10),
            'transaction_type' => 'transfer',
            'amount' => 500.00,
            'status' => 'pending',
            'from_wallet_id' => $this->wallet->id,
        ]);

        $transaction->update(['status' => 'completed', 'completed_at' => now()]);
        $transaction->update(['status' => 'reversed']);

        $statusLogs = AuditLog::where('entity_id', $transaction->id)
            ->where('action', 'transaction_status_changed')
            ->orderBy('created_at')
            ->get();

        $this->assertCount(2, $statusLogs);
        $this->assertEquals('pending', $statusLogs[0]->old_values['status']);
        $this->assertEquals('completed', $statusLogs[0]->new_values['status']);
        $this->assertEquals('completed', $statusLogs[1]->old_values['status']);
        $this->assertEquals('reversed', $statusLogs[1]->new_values['status']);
    }
}
