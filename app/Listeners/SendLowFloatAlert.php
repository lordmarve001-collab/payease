<?php

namespace App\Listeners;

use App\Events\AgentFloatBalanceDroppedLow;
use App\Jobs\SendSmsNotification;
use App\Mail\LowFloatAlert;
use Illuminate\Support\Facades\Mail;

class SendLowFloatAlert
{
    public function handle(AgentFloatBalanceDroppedLow $event): void
    {
        $agent = $event->agent->fresh('user');

        if (!$agent?->user) {
            return;
        }

        $user = $agent->user;

        if (!$user->notify_agent_activity) {
            return;
        }

        $formattedBalance = '₦' . number_format((float) $agent->float_balance, 2);

        if ($user->notify_sms) {
            rescue(function () use ($user, $formattedBalance): void {
                SendSmsNotification::dispatch(
                    $user->phone_number,
                    "Your PayEase float balance is low ({$formattedBalance}). Request a top-up to continue accepting withdrawals. -PayEase",
                );
            }, report: false);
        }

        if ($user->notify_email && $user->email) {
            rescue(function () use ($user, $formattedBalance): void {
                Mail::to($user->email)->queue(
                    new LowFloatAlert($user->full_name, $formattedBalance),
                );
            }, report: false);
        }
    }
}
