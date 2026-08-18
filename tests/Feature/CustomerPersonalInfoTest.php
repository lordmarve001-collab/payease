<?php

namespace Tests\Feature;

use App\Livewire\Customer\PersonalInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPersonalInfoTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);
    }

    public function test_personal_info_renders_user_details(): void
    {
        Livewire::test(PersonalInfo::class)
            ->assertSet('fullName', 'Test User')
            ->assertSet('email', 'test@example.com');
    }

    public function test_toggle_edit_mode(): void
    {
        Livewire::test(PersonalInfo::class)
            ->assertSet('isEditing', false)
            ->call('toggleEdit')
            ->assertSet('isEditing', true)
            ->assertSet('saved', false);
    }

    public function test_save_updates_user_info(): void
    {
        Livewire::test(PersonalInfo::class)
            ->call('toggleEdit')
            ->set('fullName', 'Updated Name')
            ->set('email', 'updated@example.com')
            ->call('save')
            ->assertSet('isEditing', false)
            ->assertSet('saved', true);

        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->full_name);
        $this->assertEquals('updated@example.com', $this->user->email);
    }

    public function test_save_requires_full_name(): void
    {
        Livewire::test(PersonalInfo::class)
            ->call('toggleEdit')
            ->set('fullName', '')
            ->call('save')
            ->assertHasErrors('fullName');
    }

    public function test_save_rejects_invalid_email(): void
    {
        Livewire::test(PersonalInfo::class)
            ->call('toggleEdit')
            ->set('email', 'not-an-email')
            ->call('save')
            ->assertHasErrors('email');
    }
}
