<?php

namespace App\Services;

use App\Jobs\SendSmsNotification;
use App\Models\Agent;
use App\Models\AgentSettlement;
use App\Models\AuditLog;
use App\Models\FloatTopUpRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FloatSettlementService
{
    public function requestTopUp(Agent $agent, float $amount, ?string $reason = null): FloatTopUpRequest
    {
        if ($agent->pendingTopUpRequest()->exists()) {
            throw new RuntimeException('You already have a pending top-up request. Please wait for it to be reviewed.');
        }

        $request = DB::transaction(function () use ($agent, $amount, $reason): FloatTopUpRequest {
            return FloatTopUpRequest::create([
                'agent_id' => $agent->id,
                'amount_requested' => $amount,
                'reason' => $reason,
                'status' => 'pending',
            ]);
        });

        dispatch(new SendSmsNotification(
            $agent->user->phone_number,
            "Your float top-up request of ₦" . number_format($amount, 2) . " has been submitted and is awaiting admin approval. -PayEase"
        ));

        return $request;
    }

    public function approveTopUp(FloatTopUpRequest $request, User $admin): FloatTopUpRequest
    {
        if ($request->status !== 'pending') {
            throw new RuntimeException('This top-up request has already been processed.');
        }

        $agent = $request->agent;

        DB::transaction(function () use ($request, $agent, $admin): void {
            $agent->increment('float_balance', $request->amount_requested);

            $request->update([
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'float_topup_approved',
                'entity_type' => 'float_topup_request',
                'entity_id' => $request->id,
                'old_values' => ['status' => 'pending'],
                'new_values' => [
                    'status' => 'approved',
                    'amount' => $request->amount_requested,
                    'agent_id' => $agent->id,
                    'new_float_balance' => $agent->fresh()->float_balance,
                ],
                'ip_address' => request()->ip(),
                'device_id' => null,
            ]);
        });

        dispatch(new SendSmsNotification(
            $agent->user->phone_number,
            "Your float top-up of ₦" . number_format($request->amount_requested, 2) . " has been approved. New float balance: ₦" . number_format($agent->fresh()->float_balance, 2) . ". -PayEase"
        ));

        return $request->fresh();
    }

    public function rejectTopUp(FloatTopUpRequest $request, User $admin, string $reason): FloatTopUpRequest
    {
        if ($request->status !== 'pending') {
            throw new RuntimeException('This top-up request has already been processed.');
        }

        DB::transaction(function () use ($request, $admin, $reason): void {
            $request->update([
                'status' => 'rejected',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'float_topup_rejected',
                'entity_type' => 'float_topup_request',
                'entity_id' => $request->id,
                'old_values' => ['status' => 'pending'],
                'new_values' => [
                    'status' => 'rejected',
                    'reason' => $reason,
                    'amount' => $request->amount_requested,
                    'agent_id' => $request->agent_id,
                ],
                'ip_address' => request()->ip(),
                'device_id' => null,
            ]);
        });

        dispatch(new SendSmsNotification(
            $request->agent->user->phone_number,
            "Your float top-up request of ₦" . number_format($request->amount_requested, 2) . " was rejected. Reason: {$reason}. -PayEase"
        ));

        return $request->fresh();
    }

    public function declareSettlement(Agent $agent, float $amount, string $bankReference, ?string $proofUrl = null): AgentSettlement
    {
        if ($amount <= 0) {
            throw new RuntimeException('Settlement amount must be greater than zero.');
        }

        if ($amount > (float) $agent->float_balance) {
            throw new RuntimeException(
                'Amount declared (₦' . number_format($amount, 2) . ') exceeds your current float balance (₦' . number_format((float) $agent->float_balance, 2) . ').'
            );
        }

        return DB::transaction(function () use ($agent, $amount, $bankReference, $proofUrl): AgentSettlement {
            return AgentSettlement::create([
                'agent_id' => $agent->id,
                'amount_declared' => $amount,
                'bank_reference' => $bankReference,
                'proof_of_deposit_url' => $proofUrl,
                'status' => 'pending_verification',
            ]);
        });
    }

    public function verifySettlement(AgentSettlement $settlement, User $admin): AgentSettlement
    {
        if ($settlement->status !== 'pending_verification') {
            throw new RuntimeException('This settlement has already been processed.');
        }

        $agent = $settlement->agent;

        DB::transaction(function () use ($settlement, $agent, $admin): void {
            $agent->decrement('float_balance', $settlement->amount_declared);
            $agent->update(['last_settlement_at' => now()]);

            $settlement->update([
                'status' => 'verified',
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'settlement_verified',
                'entity_type' => 'agent_settlement',
                'entity_id' => $settlement->id,
                'old_values' => ['status' => 'pending_verification'],
                'new_values' => [
                    'status' => 'verified',
                    'amount' => $settlement->amount_declared,
                    'agent_id' => $agent->id,
                    'new_float_balance' => $agent->fresh()->float_balance,
                ],
                'ip_address' => request()->ip(),
                'device_id' => null,
            ]);
        });

        dispatch(new SendSmsNotification(
            $agent->user->phone_number,
            "Your settlement of ₦" . number_format($settlement->amount_declared, 2) . " has been verified. New float balance: ₦" . number_format($agent->fresh()->float_balance, 2) . ". -PayEase"
        ));

        return $settlement->fresh();
    }

    public function rejectSettlement(AgentSettlement $settlement, User $admin, string $reason): AgentSettlement
    {
        if ($settlement->status !== 'pending_verification') {
            throw new RuntimeException('This settlement has already been processed.');
        }

        DB::transaction(function () use ($settlement, $admin, $reason): void {
            $settlement->update([
                'status' => 'rejected',
                'verified_by' => $admin->id,
                'verified_at' => now(),
                'rejection_reason' => $reason,
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'settlement_rejected',
                'entity_type' => 'agent_settlement',
                'entity_id' => $settlement->id,
                'old_values' => ['status' => 'pending_verification'],
                'new_values' => [
                    'status' => 'rejected',
                    'reason' => $reason,
                    'amount' => $settlement->amount_declared,
                    'agent_id' => $settlement->agent_id,
                ],
                'ip_address' => request()->ip(),
                'device_id' => null,
            ]);
        });

        dispatch(new SendSmsNotification(
            $settlement->agent->user->phone_number,
            "Your settlement of ₦" . number_format($settlement->amount_declared, 2) . " was rejected. Reason: {$reason}. -PayEase"
        ));

        return $settlement->fresh();
    }

    public function getPendingTopUpRequests(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return FloatTopUpRequest::query()
            ->where('status', 'pending')
            ->with(['agent.user'])
            ->latest()
            ->paginate($perPage);
    }

    public function getPendingSettlements(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return AgentSettlement::query()
            ->where('status', 'pending_verification')
            ->with(['agent.user'])
            ->latest()
            ->paginate($perPage);
    }
}
