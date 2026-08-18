<?php

namespace Tests\Feature;

use App\Jobs\SendSmsNotification;
use App\Models\Agent;
use App\Models\AgentSettlement;
use App\Models\FloatTopUpRequest;
use App\Models\User;
use App\Services\FloatSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FloatSettlementTest extends TestCase
{
    use RefreshDatabase;

    private User $agentUser;
    private User $admin;
    private Agent $agent;
    private FloatSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['customer', 'agent', 'admin', 'super_admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->admin = User::create([
            'phone_number' => '08030000100',
            'full_name' => 'Admin User',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
        ]);
        $this->admin->assignRole('admin');

        $this->agentUser = User::create([
            'phone_number' => '08030000101',
            'full_name' => 'Agent Person',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
        ]);
        $this->agentUser->assignRole('agent');

        $this->agent = Agent::create([
            'user_id' => $this->agentUser->id,
            'business_name' => 'Agent Shop',
            'business_address' => '123 Main St',
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
            'lga' => 'Ikeja',
            'state' => 'Lagos',
            'float_balance' => 100000,
            'max_float' => 500000,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->service = app(FloatSettlementService::class);
    }

    public function test_agent_requests_top_up_creates_pending_request(): void
    {
        $request = $this->service->requestTopUp($this->agent, 50000, 'Restocking for weekend demand');

        $this->assertDatabaseHas('float_topup_requests', [
            'id' => $request->id,
            'agent_id' => $this->agent->id,
            'amount_requested' => 50000,
            'reason' => 'Restocking for weekend demand',
            'status' => 'pending',
        ]);
    }

    public function test_agent_cannot_request_second_top_up_while_one_pending(): void
    {
        $this->service->requestTopUp($this->agent, 30000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('pending top-up request');

        $this->service->requestTopUp($this->agent, 20000);
    }

    public function test_approve_top_up_increases_float_and_sends_sms(): void
    {
        Queue::fake();

        $request = $this->service->requestTopUp($this->agent, 50000);
        $this->service->approveTopUp($request, $this->admin);

        $this->assertDatabaseHas('float_topup_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'reviewed_by' => $this->admin->id,
        ]);

        $this->agent->refresh();
        $this->assertEquals(150000.0, (float) $this->agent->float_balance);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'float_topup_approved',
            'entity_type' => 'float_topup_request',
        ]);

        Queue::assertPushed(SendSmsNotification::class, function ($job) {
            return str_contains($job->message, 'has been approved');
        });
    }

    public function test_reject_top_up_does_not_change_float_and_sends_sms(): void
    {
        Queue::fake();

        $request = $this->service->requestTopUp($this->agent, 50000);
        $this->service->rejectTopUp($request, $this->admin, 'Insufficient business volume');

        $this->assertDatabaseHas('float_topup_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'rejection_reason' => 'Insufficient business volume',
        ]);

        $this->agent->refresh();
        $this->assertEquals(100000.0, (float) $this->agent->float_balance);

        Queue::assertPushed(SendSmsNotification::class, function ($job) {
            return str_contains($job->message, 'was rejected');
        });
    }

    public function test_declare_settlement_creates_pending_verification(): void
    {
        $settlement = $this->service->declareSettlement($this->agent, 30000, 'TXN-12345-ABCDE');

        $this->assertDatabaseHas('agent_settlements', [
            'id' => $settlement->id,
            'agent_id' => $this->agent->id,
            'amount_declared' => 30000,
            'bank_reference' => 'TXN-12345-ABCDE',
            'status' => 'pending_verification',
        ]);

        $this->agent->refresh();
        $this->assertEquals(100000.0, (float) $this->agent->float_balance, 'Float should not change on declaration');
    }

    public function test_declare_settlement_rejects_amount_exceeding_float(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exceeds your current float balance');

        $this->service->declareSettlement($this->agent, 200000, 'TXN-OVERFLOW');
    }

    public function test_verify_settlement_decreases_float_and_sends_sms(): void
    {
        Queue::fake();

        $settlement = $this->service->declareSettlement($this->agent, 40000, 'TXN-VERIFY-001');
        $this->service->verifySettlement($settlement, $this->admin);

        $this->assertDatabaseHas('agent_settlements', [
            'id' => $settlement->id,
            'status' => 'verified',
            'verified_by' => $this->admin->id,
        ]);

        $this->agent->refresh();
        $this->assertEquals(60000.0, (float) $this->agent->float_balance);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settlement_verified',
            'entity_type' => 'agent_settlement',
        ]);

        Queue::assertPushed(SendSmsNotification::class, function ($job) {
            return str_contains($job->message, 'has been verified');
        });
    }

    public function test_reject_settlement_does_not_change_float_and_sends_sms(): void
    {
        Queue::fake();

        $settlement = $this->service->declareSettlement($this->agent, 25000, 'TXN-REJECT-001');
        $this->service->rejectSettlement($settlement, $this->admin, 'Bank reference does not match our records');

        $this->assertDatabaseHas('agent_settlements', [
            'id' => $settlement->id,
            'status' => 'rejected',
            'rejection_reason' => 'Bank reference does not match our records',
        ]);

        $this->agent->refresh();
        $this->assertEquals(100000.0, (float) $this->agent->float_balance);

        Queue::assertPushed(SendSmsNotification::class, function ($job) {
            return str_contains($job->message, 'was rejected');
        });
    }

    public function test_cannot_approve_already_processed_top_up(): void
    {
        $request = $this->service->requestTopUp($this->agent, 10000);
        $this->service->approveTopUp($request, $this->admin);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already been processed');

        $this->service->approveTopUp($request, $this->admin);
    }

    public function test_cannot_verify_already_processed_settlement(): void
    {
        $settlement = $this->service->declareSettlement($this->agent, 10000, 'TXN-DUP');
        $this->service->verifySettlement($settlement, $this->admin);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already been processed');

        $this->service->verifySettlement($settlement, $this->admin);
    }
}
