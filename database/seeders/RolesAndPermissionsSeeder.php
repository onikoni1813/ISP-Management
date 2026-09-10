<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define Granular Permissions
        $permissionsByGroup = [
            'customers' => [
                'customers.view' => 'View customer list and profile',
                'customers.create' => 'Create new customers',
                'customers.update' => 'Update customer information',
                'customers.delete' => 'Archive or delete customers',
            ],
            'connections' => [
                'connections.view' => 'View connection information',
                'connections.create' => 'Create new connection',
                'connections.update' => 'Update connection settings',
            ],
            'pppoe' => [
                'pppoe.view_username' => 'View PPPoE username',
                'pppoe.view_password' => 'View PPPoE encrypted password',
                'pppoe.update' => 'Update PPPoE credentials',
            ],
            'packages' => [
                'packages.view' => 'View packages and pricing',
                'packages.create' => 'Create new package',
                'packages.update' => 'Update package and pricing',
                'packages.archive' => 'Archive package',
            ],
            'areas' => [
                'areas.view' => 'View areas and zones',
                'areas.create' => 'Create new area',
                'areas.update' => 'Update area',
                'areas.archive' => 'Archive area',
            ],
            'billing' => [
                'billing.view' => 'View invoices and due amounts',
                'billing.create' => 'Create invoices',
                'billing.collect' => 'Collect payment for bills',
                'billing.adjust' => 'Adjust invoices',
                'billing.refund' => 'Refund or reverse transactions',
            ],
            'payments' => [
                'payments.view' => 'View payments history',
                'payments.create' => 'Create payment records',
                'payments.reverse' => 'Reverse payment records',
            ],
            'renewals' => [
                'renewals.view' => 'View renewal records',
                'renewals.create' => 'Perform package renewals',
            ],
            'complaints' => [
                'complaints.view' => 'View support tickets and complaints',
                'complaints.create' => 'Create support ticket',
                'complaints.assign' => 'Assign tickets to technicians',
                'complaints.update' => 'Update ticket notes and status',
                'complaints.resolve' => 'Resolve tickets',
                'complaints.close' => 'Close tickets',
            ],
            'staff' => [
                'staff.view' => 'View staff profiles',
                'staff.create' => 'Add new staff',
                'staff.update' => 'Edit staff profiles',
                'staff.permissions' => 'Manage staff roles and permissions',
                'staff.salary' => 'Manage staff salary and payouts',
            ],
            'expenses' => [
                'expenses.view' => 'View operational expenses',
                'expenses.create' => 'Record business expenses',
                'expenses.update' => 'Edit expenses',
                'expenses.void' => 'Void expenses',
            ],
            'accounts' => [
                'accounts.view' => 'View accounts (Cash, Bank, bKash, etc.)',
                'accounts.create' => 'Create account',
                'accounts.transfer' => 'Transfer balance between accounts',
            ],
            'reports' => [
                'reports.view' => 'View revenue, collection and P/L reports',
            ],
            'sms' => [
                'sms.view' => 'View SMS logs',
                'sms.send' => 'Send SMS messages',
                'sms.templates' => 'Manage SMS templates',
                'sms.settings' => 'Configure SMS gateways',
            ],
            'audit' => [
                'audit.view' => 'View audit trail logs',
            ],
            'website' => [
                'website.manage' => 'Manage public website content and notices',
            ],
            'settings' => [
                'settings.manage' => 'Manage general application settings',
            ],
        ];

        $allPermissionModels = [];

        foreach ($permissionsByGroup as $group => $perms) {
            foreach ($perms as $slug => $name) {
                $allPermissionModels[$slug] = Permission::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'group' => $group,
                        'description' => $name,
                    ]
                );
            }
        }

        // 2. Create Roles
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'System Administrator with full access']
        );

        $staffRole = Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff / Technician', 'description' => 'Field staff, technician and collection boy']
        );

        $customerRole = Role::firstOrCreate(
            ['slug' => 'customer'],
            ['name' => 'Customer', 'description' => 'Subscribed internet consumer']
        );

        // Assign all permissions to Admin
        $adminRole->permissions()->sync(collect($allPermissionModels)->pluck('id'));

        // Assign standard field permissions to Staff
        $staffPermissions = [
            'customers.view', 'customers.create',
            'connections.view',
            'pppoe.view_username',
            'packages.view',
            'areas.view',
            'billing.view', 'billing.collect',
            'payments.view', 'payments.create',
            'renewals.view', 'renewals.create',
            'complaints.view', 'complaints.create', 'complaints.update', 'complaints.resolve',
        ];

        $staffPermIds = collect($staffPermissions)
            ->map(fn($slug) => $allPermissionModels[$slug]->id ?? null)
            ->filter();

        $staffRole->permissions()->sync($staffPermIds);

        // 3. Seed Default Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@pirgachainternet.com'],
            [
                'name' => 'Pirgacha Admin',
                'phone' => '01711000000',
                'password' => Hash::make('admin123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // 4. Seed Default Staff User
        $staff = User::firstOrCreate(
            ['email' => 'staff@pirgachainternet.com'],
            [
                'name' => 'Field Technician Rahim',
                'phone' => '01722000000',
                'password' => Hash::make('staff123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $staff->assignRole('staff');

        // 5. Seed Default Customer User
        $customer = User::firstOrCreate(
            ['email' => 'customer@pirgachainternet.com'],
            [
                'name' => 'Karim Customer',
                'phone' => '01733000000',
                'password' => Hash::make('customer123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $customer->assignRole('customer');
    }
}
