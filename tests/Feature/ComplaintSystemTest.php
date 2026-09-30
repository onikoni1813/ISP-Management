<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ComplaintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected Customer $customer;
    protected ComplaintService $complaintService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $perms = ['complaints.view', 'complaints.create', 'complaints.update', 'complaints.resolve'];
        foreach ($perms as $p) {
            $model = Permission::firstOrCreate(['slug' => $p], ['name' => $p]);
            $staffRole->permissions()->attach($model);
        }

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-TKT-1',
            'name' => 'Nurul Islam',
            'status' => 'active',
            'join_date' => now()->toDateString(),
        ]);

        $this->complaintService = app(ComplaintService::class);
    }

    public function test_complaint_creation_logs_audit_and_status_history(): void
    {
        $complaint = $this->complaintService->createComplaint($this->customer, [
            'subject' => 'Internet not working / Red LOS blinking',
            'description' => 'Since morning optical fiber fiber LOS light is flashing red.',
            'priority' => 'urgent',
        ], $this->admin->id);

        $this->assertNotNull($complaint->id);
        $this->assertEquals('open', $complaint->status);
        $this->assertStringStartsWith('TKT-', $complaint->complaint_number);

        $this->assertDatabaseHas('complaint_status_histories', [
            'complaint_id' => $complaint->id,
            'new_status' => 'open',
            'changed_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'complaint_created',
            'entity_id' => $complaint->id,
        ]);
    }

    public function test_technician_assignment_and_status_transition_to_resolved(): void
    {
        $complaint = $this->complaintService->createComplaint($this->customer, [
            'subject' => 'Slow speed issue',
            'description' => 'Speed test shows only 2 Mbps instead of 10 Mbps',
            'priority' => 'normal',
        ], $this->admin->id);

        // Assign to technician
        $this->complaintService->assignComplaint($complaint, $this->staff->id, $this->admin->id);

        $complaint->refresh();
        $this->assertEquals('assigned', $complaint->status);
        $this->assertEquals($this->staff->id, $complaint->assigned_to);

        // Resolve ticket by technician
        $this->complaintService->updateStatus($complaint, 'resolved', $this->staff->id, 'Re-spliced fiber connector at DB box, speed restored to 10 Mbps.');

        $complaint->refresh();
        $this->assertEquals('resolved', $complaint->status);
        $this->assertEquals($this->staff->id, $complaint->resolved_by);
        $this->assertNotNull($complaint->resolved_at);
        $this->assertStringContainsString('Re-spliced', $complaint->resolution_note);
    }

    public function test_comments_can_be_added_to_complaint(): void
    {
        $complaint = $this->complaintService->createComplaint($this->customer, [
            'subject' => 'Router reset required',
            'description' => 'Forgot WiFi password',
        ], $this->admin->id);

        $comment = $this->complaintService->addComment($complaint, 'Technician visited premises and reconfigured WiFi SSID.', $this->staff->id);

        $this->assertDatabaseHas('complaint_comments', [
            'complaint_id' => $complaint->id,
            'user_id' => $this->staff->id,
            'comment' => 'Technician visited premises and reconfigured WiFi SSID.',
        ]);
    }

    public function test_admin_can_delete_individual_complaint(): void
    {
        $complaint = $this->complaintService->createComplaint($this->customer, [
            'subject' => 'Ticket to be deleted',
            'description' => 'Test description',
        ], $this->admin->id);

        $this->complaintService->addComment($complaint, 'Test comment', $this->staff->id);

        $response = $this->actingAs($this->admin)->delete(route('admin.complaints.destroy', $complaint->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('complaints', ['id' => $complaint->id]);
        $this->assertDatabaseMissing('complaint_comments', ['complaint_id' => $complaint->id]);
        $this->assertDatabaseMissing('complaint_status_histories', ['complaint_id' => $complaint->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'complaint_deleted',
            'entity_id' => $complaint->id,
        ]);
    }

    public function test_admin_can_bulk_delete_complaints(): void
    {
        $c1 = $this->complaintService->createComplaint($this->customer, [
            'subject' => 'Bulk ticket 1',
            'description' => 'Desc 1',
        ], $this->admin->id);

        $c2 = $this->complaintService->createComplaint($this->customer, [
            'subject' => 'Bulk ticket 2',
            'description' => 'Desc 2',
        ], $this->admin->id);

        $response = $this->actingAs($this->admin)->delete(route('admin.complaints.bulk-destroy'), [
            'complaint_ids' => [$c1->id, $c2->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('complaints', ['id' => $c1->id]);
        $this->assertDatabaseMissing('complaints', ['id' => $c2->id]);
    }

    public function test_admin_can_clear_complaint_history(): void
    {
        $openTicket = $this->complaintService->createComplaint($this->customer, [
            'subject' => 'Open issue',
            'description' => 'Should not be cleared when filtering resolved',
        ], $this->admin->id);

        $resolvedTicket = $this->complaintService->createComplaint($this->customer, [
            'subject' => 'Resolved issue',
            'description' => 'Should be cleared',
        ], $this->admin->id);

        $this->complaintService->updateStatus($resolvedTicket, 'resolved', $this->admin->id, 'Fixed');

        $response = $this->actingAs($this->admin)->delete(route('admin.complaints.clear-history'), [
            'filter_type' => 'resolved',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('complaints', ['id' => $resolvedTicket->id]);
        $this->assertDatabaseHas('complaints', ['id' => $openTicket->id]);
    }

    public function test_staff_cannot_delete_complaints(): void
    {
        $complaint = $this->complaintService->createComplaint($this->customer, [
            'subject' => 'Staff delete attempt',
            'description' => 'Staff should get 403',
        ], $this->admin->id);

        $response = $this->actingAs($this->staff)->delete(route('admin.complaints.destroy', $complaint->id));
        $response->assertForbidden();

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id]);
    }
}
