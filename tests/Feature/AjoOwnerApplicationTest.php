<?php

namespace Tests\Feature;

use App\Models\AjoOwner;
use App\Models\User;
use App\Services\AjoOwnerApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AjoOwnerApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $tier2User;
    protected User $tier1User;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->tier2User = User::create([
            'full_name' => 'Tier Two User',
            'kyc_level' => 2,
            'phone_number' => '7012345678',
        ]);
        $this->tier2User->assignRole('customer');

        $this->tier1User = User::create([
            'full_name' => 'Tier One User',
            'kyc_level' => 1,
            'phone_number' => '8012345678',
        ]);
        $this->tier1User->assignRole('customer');

        $this->admin = User::create([
            'full_name' => 'Super Admin',
            'phone_number' => '9012345678',
        ]);
        $this->admin->assignRole('super_admin');
    }

    #[Test]
    public function submit_application_rejects_tier1_user()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must complete Tier 2 KYC verification');

        app(AjoOwnerApplicationService::class)->submitApplication($this->tier1User, [
            'business_name' => 'Market Ajo Group',
            'business_description' => 'Serving market traders',
            'business_address' => 'Ile-Epo Market',
            'lga' => 'Alimosho',
            'state' => 'Lagos',
            'planned_groups' => 2,
            'members_per_group' => 10,
            'agent_assignment_preference' => 'not_sure',
        ]);
    }

    #[Test]
    public function submit_application_creates_pending_ajo_owner()
    {
        Queue::fake();

        $owner = app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'Market Ajo Group',
            'business_description' => 'Serving market traders at Ile-Epo market',
            'business_address' => 'Ile-Epo Market, Oke-Odo',
            'lga' => 'Alimosho',
            'state' => 'Lagos',
            'has_experience' => true,
            'planned_groups' => 3,
            'members_per_group' => 15,
            'agent_assignment_preference' => 'have_agents',
            'reference_contact_name' => 'John Doe',
            'reference_contact_phone' => '7011111111',
        ]);

        $this->assertDatabaseHas('ajo_owners', [
            'id' => $owner->id,
            'user_id' => $this->tier2User->id,
            'business_name' => 'Market Ajo Group',
            'status' => 'pending',
            'planned_groups' => 3,
            'members_per_group' => 15,
            'agent_assignment_preference' => 'have_agents',
        ]);

        $this->assertEquals('pending', $owner->status);
    }

    #[Test]
    public function submit_application_rejects_duplicate_pending()
    {
        Queue::fake();

        app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'First Application',
            'business_description' => 'Desc',
            'business_address' => 'Addr',
            'lga' => 'Lagos',
            'state' => 'Lagos',
            'planned_groups' => 1,
            'members_per_group' => 5,
            'agent_assignment_preference' => 'not_sure',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already have a pending');

        app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'Second Application',
            'business_description' => 'Desc',
            'business_address' => 'Addr',
            'lga' => 'Lagos',
            'state' => 'Lagos',
            'planned_groups' => 1,
            'members_per_group' => 5,
            'agent_assignment_preference' => 'not_sure',
        ]);
    }

    #[Test]
    public function submit_application_rejects_duplicate_active()
    {
        Queue::fake();

        $owner = app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'My Ajo Business',
            'business_description' => 'Desc',
            'business_address' => 'Addr',
            'lga' => 'Lagos',
            'state' => 'Lagos',
            'planned_groups' => 1,
            'members_per_group' => 5,
            'agent_assignment_preference' => 'not_sure',
        ]);

        $owner->update(['status' => 'active']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already an active');

        app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'Another Business',
            'business_description' => 'Desc',
            'business_address' => 'Addr',
            'lga' => 'Lagos',
            'state' => 'Lagos',
            'planned_groups' => 1,
            'members_per_group' => 5,
            'agent_assignment_preference' => 'not_sure',
        ]);
    }

    #[Test]
    public function admin_approval_activates_ajo_owner_and_assigns_role()
    {
        Queue::fake();

        $owner = app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'Approved Ajo',
            'business_description' => 'Desc',
            'business_address' => 'Addr',
            'lga' => 'Ikeja',
            'state' => 'Lagos',
            'planned_groups' => 2,
            'members_per_group' => 10,
            'agent_assignment_preference' => 'needs_help',
        ]);

        app(AjoOwnerApplicationService::class)->approve($owner, $this->admin);

        $this->assertDatabaseHas('ajo_owners', [
            'id' => $owner->id,
            'status' => 'active',
            'approved_by' => $this->admin->id,
        ]);

        $this->assertNotNull($owner->fresh()->approved_at);
        $this->assertTrue($this->tier2User->fresh()->hasRole('ajo_owner'));
    }

    #[Test]
    public function admin_rejection_records_reason()
    {
        Queue::fake();

        $owner = app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'Rejected Ajo',
            'business_description' => 'Desc',
            'business_address' => 'Addr',
            'lga' => 'Lagos',
            'state' => 'Lagos',
            'planned_groups' => 1,
            'members_per_group' => 5,
            'agent_assignment_preference' => 'not_sure',
        ]);

        app(AjoOwnerApplicationService::class)->reject($owner, $this->admin, 'Business description is insufficient. Please provide more detail.');

        $this->assertDatabaseHas('ajo_owners', [
            'id' => $owner->id,
            'status' => 'rejected',
            'rejection_reason' => 'Business description is insufficient. Please provide more detail.',
        ]);
    }

    #[Test]
    public function rejected_applicant_can_reapply()
    {
        Queue::fake();

        $owner = app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'First Try',
            'business_description' => 'Desc',
            'business_address' => 'Addr',
            'lga' => 'Lagos',
            'state' => 'Lagos',
            'planned_groups' => 1,
            'members_per_group' => 5,
            'agent_assignment_preference' => 'not_sure',
        ]);

        app(AjoOwnerApplicationService::class)->reject($owner, $this->admin, 'Insufficient detail');

        $secondOwner = app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'Second Try',
            'business_description' => 'A detailed description of my business serving the community',
            'business_address' => 'New Address',
            'lga' => 'Ikeja',
            'state' => 'Lagos',
            'planned_groups' => 3,
            'members_per_group' => 20,
            'agent_assignment_preference' => 'have_agents',
        ]);

        $this->assertEquals('pending', $secondOwner->status);
        $this->assertEquals($owner->id, $secondOwner->id); // Same record updated
    }

    #[Test]
    public function approved_ajo_owner_can_access_ajo_owner_dashboard()
    {
        Queue::fake();

        $owner = app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'Dashboard Test Ajo',
            'business_description' => 'Desc',
            'business_address' => 'Addr',
            'lga' => 'Lagos',
            'state' => 'Lagos',
            'planned_groups' => 1,
            'members_per_group' => 5,
            'agent_assignment_preference' => 'not_sure',
        ]);

        app(AjoOwnerApplicationService::class)->approve($owner, $this->admin);

        $response = $this->actingAs($this->tier2User)->get('/ajo-owner/dashboard');
        $response->assertStatus(200);
    }

    #[Test]
    public function become_ajo_owner_page_returns_404_when_marketing_removed()
    {
        $response = $this->get('/become-an-ajo-owner');
        $response->assertNotFound();
    }

    #[Test]
    public function become_ajo_owner_marketing_returns_404_for_tier2()
    {
        $response = $this->actingAs($this->tier2User)->get('/become-an-ajo-owner');
        $response->assertNotFound();
    }

    #[Test]
    public function become_ajo_owner_marketing_returns_404_for_tier1()
    {
        $response = $this->actingAs($this->tier1User)->get('/become-an-ajo-owner');
        $response->assertNotFound();
    }

    #[Test]
    public function admin_ajo_owners_page_shows_applications()
    {
        Queue::fake();

        app(AjoOwnerApplicationService::class)->submitApplication($this->tier2User, [
            'business_name' => 'Admin View Test',
            'business_description' => 'Desc',
            'business_address' => 'Addr',
            'lga' => 'Lagos',
            'state' => 'Lagos',
            'planned_groups' => 2,
            'members_per_group' => 10,
            'agent_assignment_preference' => 'needs_help',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/ajo-owners');
        $response->assertStatus(200);
        $response->assertSee('Admin View Test');
        $response->assertSee('pending');
    }

    #[Test]
    public function admin_sidebar_includes_ajo_owners_link()
    {
        $response = $this->actingAs($this->admin)->get('/admin/overview');
        $response->assertStatus(200);
        $response->assertSee('/admin/ajo-owners');
    }
}
