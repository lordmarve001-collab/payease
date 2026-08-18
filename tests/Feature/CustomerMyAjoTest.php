<?php

namespace Tests\Feature;

use App\Livewire\Customer\MyAjo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerMyAjoTest extends TestCase
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

    public function test_my_ajo_renders_empty_state(): void
    {
        Livewire::test(MyAjo::class)
            ->assertSee('Ajo')
            ->assertSee('Not in any Ajo group');
    }
}
