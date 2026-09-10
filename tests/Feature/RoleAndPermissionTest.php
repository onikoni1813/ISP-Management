<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard(): void
    {
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    public function test_staff_cannot_access_admin_dashboard(): void
    {
        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $staff = User::factory()->create();
        $staff->roles()->attach($staffRole);

        $response = $this->actingAs($staff)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_staff_can_access_staff_dashboard(): void
    {
        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $staff = User::factory()->create();
        $staff->roles()->attach($staffRole);

        $response = $this->actingAs($staff)->get('/staff/dashboard');

        $response->assertStatus(200);
    }

    public function test_admin_passes_all_permission_gates(): void
    {
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $this->assertTrue(Gate::forUser($admin)->allows('billing.collect'));
        $this->assertTrue(Gate::forUser($admin)->allows('pppoe.view_password'));
    }

    public function test_staff_only_has_assigned_permission(): void
    {
        $perm = Permission::create(['name' => 'Collect Bill', 'slug' => 'billing.collect']);
        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $staffRole->permissions()->attach($perm);

        $staff = User::factory()->create();
        $staff->roles()->attach($staffRole);

        $this->assertTrue(Gate::forUser($staff)->allows('billing.collect'));
        $this->assertFalse(Gate::forUser($staff)->allows('pppoe.view_password'));
    }
}
