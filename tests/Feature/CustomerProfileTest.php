<?php

namespace Tests\Feature;

use App\Livewire\Customer\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
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
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);
    }

    public function test_profile_renders_user_info(): void
    {
        Livewire::test(Profile::class)
            ->assertSee('Test User')
            ->assertSee('+234 348012345678');
    }

    public function test_toggle_change_pin_form(): void
    {
        Livewire::test(Profile::class)
            ->assertSet('showChangePinForm', false)
            ->call('toggleChangePinForm')
            ->assertSet('showChangePinForm', true)
            ->call('toggleChangePinForm')
            ->assertSet('showChangePinForm', false);
    }

    public function test_change_pin_requires_current_pin(): void
    {
        Livewire::test(Profile::class)
            ->call('toggleChangePinForm')
            ->set('newPin', '584927')
            ->set('newPinConfirmation', '584927')
            ->call('changePin')
            ->assertHasErrors('currentPin');
    }

    public function test_change_pin_rejects_incorrect_current_pin(): void
    {
        Livewire::test(Profile::class)
            ->call('toggleChangePinForm')
            ->set('currentPin', '999999')
            ->set('newPin', '584927')
            ->set('newPinConfirmation', '584927')
            ->call('changePin')
            ->assertHasErrors('currentPin');
    }

    public function test_change_pin_requires_new_pin_confirmation_match(): void
    {
        Livewire::test(Profile::class)
            ->call('toggleChangePinForm')
            ->set('currentPin', '123456')
            ->set('newPin', '584927')
            ->set('newPinConfirmation', '000000')
            ->call('changePin')
            ->assertHasErrors('newPinConfirmation');
    }

    public function test_change_pin_rejects_weak_new_pin(): void
    {
        Livewire::test(Profile::class)
            ->call('toggleChangePinForm')
            ->set('currentPin', '123456')
            ->set('newPin', '654321')
            ->set('newPinConfirmation', '654321')
            ->call('changePin')
            ->assertHasErrors('newPin');
    }

    public function test_change_pin_successfully(): void
    {
        Livewire::test(Profile::class)
            ->call('toggleChangePinForm')
            ->set('currentPin', '123456')
            ->set('newPin', '584927')
            ->set('newPinConfirmation', '584927')
            ->call('changePin')
            ->assertSet('showChangePinForm', false);

        $this->user->refresh();
        $this->assertTrue(Hash::check('584927', (string) $this->user->pin_hash));
    }

    public function test_logout_redirects_to_login(): void
    {
        Livewire::test(Profile::class)
            ->call('logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
