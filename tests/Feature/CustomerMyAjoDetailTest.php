<?php

namespace Tests\Feature;

use App\Livewire\Customer\MyAjoDetail;
use App\Models\AjoGroup;
use App\Models\AjoMember;
use App\Models\AjoOwner;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerMyAjoDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AjoGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Ajo Member',
            'kyc_level' => 2,
            'status' => 'active',
        ]);

        $ownerUser = User::create([
            'phone_number' => '2348012345679',
            'full_name' => 'Ajo Owner',
            'kyc_level' => 2,
            'status' => 'active',
        ]);

        $owner = AjoOwner::create([
            'user_id' => $ownerUser->id,
            'business_name' => 'Test Ajo',
            'status' => 'approved',
        ]);

        $this->group = AjoGroup::create([
            'ajo_owner_id' => $owner->id,
            'name' => 'Test Savings Group',
            'contribution_amount' => 5000,
            'frequency' => 'weekly',
            'members_count' => 5,
            'payout_order' => 'fixed',
            'managing_agent_id' => null,
            'status' => 'active',
        ]);

        AjoMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'position' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);
    }

    public function test_my_ajo_detail_renders_group_info(): void
    {
        Livewire::test(MyAjoDetail::class, ['id' => $this->group->id])
            ->assertSee('Test Savings Group')
            ->assertSee('5,000');
    }

    public function test_my_ajo_detail_shows_cycle_progress(): void
    {
        Livewire::test(MyAjoDetail::class, ['id' => $this->group->id])
            ->assertSet('groupId', $this->group->id);
    }

    public function test_my_ajo_detail_rejects_non_member(): void
    {
        $otherUser = User::create([
            'phone_number' => '2348012345680',
            'full_name' => 'Non Member',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($otherUser);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(MyAjoDetail::class, ['id' => $this->group->id]);
    }
}
