<?php

namespace Tests\Feature;

use App\Livewire\Customer\HelpSupport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerHelpSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Test User',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($user);
    }

    public function test_help_support_renders(): void
    {
        Livewire::test(HelpSupport::class)
            ->assertSee('Help')
            ->assertSee('Support');
    }
}
