<?php

namespace Tests\Feature;

use App\Livewire\Customer\NotificationsSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerNotificationsSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Test User',
            'kyc_level' => 1,
            'status' => 'active',
            'notify_sms' => true,
            'notify_email' => false,
        ]);

        $this->actingAs($this->user);
    }

    public function test_notifications_renders_with_user_settings(): void
    {
        Livewire::test(NotificationsSettings::class)
            ->assertSet('notifySms', true)
            ->assertSet('notifyEmail', false);
    }

    public function test_toggle_sms_notification(): void
    {
        Livewire::test(NotificationsSettings::class)
            ->assertSet('notifySms', true)
            ->set('notifySms', false)
            ->assertSet('notifySms', false);

        $this->user->refresh();
        $this->assertFalse((bool) $this->user->notify_sms);
    }

    public function test_toggle_email_notification(): void
    {
        Livewire::test(NotificationsSettings::class)
            ->assertSet('notifyEmail', false)
            ->set('notifyEmail', true)
            ->assertSet('notifyEmail', true);

        $this->user->refresh();
        $this->assertTrue((bool) $this->user->notify_email);
    }

    public function test_toggle_payout_notification(): void
    {
        Livewire::test(NotificationsSettings::class)
            ->set('notifyPayout', true)
            ->assertSet('notifyPayout', true);

        $this->user->refresh();
        $this->assertTrue((bool) $this->user->notify_payout);
    }

    public function test_toggle_contribution_notification(): void
    {
        Livewire::test(NotificationsSettings::class)
            ->set('notifyContribution', true)
            ->assertSet('notifyContribution', true);

        $this->user->refresh();
        $this->assertTrue((bool) $this->user->notify_contribution);
    }

    public function test_toggle_agent_activity_notification(): void
    {
        Livewire::test(NotificationsSettings::class)
            ->set('notifyAgentActivity', true)
            ->assertSet('notifyAgentActivity', true);

        $this->user->refresh();
        $this->assertTrue((bool) $this->user->notify_agent_activity);
    }
}
