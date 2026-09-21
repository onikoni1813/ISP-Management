<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StaffManagementController extends Controller
{
    /**
     * Display a listing of staff and administrative personnel.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('staff.view');

        $search = $request->input('search');
        $status = $request->input('status');

        $staffUsers = User::with(['roles', 'permissions'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['staff', 'admin']);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest('id')
            ->get()
            ->map(function ($user) {
                $today = now()->toDateString();
                $todayCollections = (float) Payment::where('collected_by', $user->id)
                    ->whereDate('paid_at', $today)
                    ->where('status', 'completed')
                    ->sum('amount');

                $assignedComplaints = Complaint::where('assigned_to', $user->id)
                    ->whereIn('status', ['open', 'assigned', 'in_progress'])
                    ->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'username' => $user->username,
                    'status' => $user->status,
                    'roles' => $user->roles->pluck('name', 'slug'),
                    'is_admin' => $user->hasRole('admin'),
                    'today_collections' => $todayCollections,
                    'assigned_complaints' => $assignedComplaints,
                    'created_at' => $user->created_at?->format('d M Y'),
                ];
            });

        return Inertia::render('Admin/Staff/Index', [
            'staffMembers' => $staffUsers,
            'filters' => $request->only(['search', 'status']),
            'totalStaffCount' => $staffUsers->count(),
            'activeStaffCount' => $staffUsers->where('status', 'active')->count(),
            'inactiveStaffCount' => $staffUsers->where('status', 'inactive')->count(),
        ]);
    }

    /**
     * Store a newly created staff member in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('staff.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'role' => ['required', 'string', 'in:staff,admin'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'username' => $validated['username'] ?: null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
            'email_verified_at' => now(),
        ]);

        $roleSlug = $validated['role'];
        $user->assignRole($roleSlug);

        // Assign default field permissions if staff
        if ($roleSlug === 'staff') {
            $defaultPerms = [
                'customers.view', 'customers.create',
                'connections.view',
                'pppoe.view_username',
                'billing.view', 'billing.collect',
                'payments.view', 'payments.create',
                'renewals.view', 'renewals.create',
                'complaints.view', 'complaints.create', 'complaints.update', 'complaints.resolve',
            ];
            $user->givePermissionTo(...$defaultPerms);
        }

        AuditLog::log('staff_created', 'user', $user, null, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roleSlug,
            'status' => $user->status,
        ]);

        return redirect()->route('admin.staff-members.index')
            ->with('success', "Staff member '{$user->name}' created successfully with {$roleSlug} privileges.");
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, User $staff_member): RedirectResponse
    {
        Gate::authorize('staff.update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff_member->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($staff_member->id)],
            'username' => ['nullable', 'string', 'max:100', Rule::unique('users')->ignore($staff_member->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'role' => ['required', 'string', 'in:staff,admin'],
        ]);

        // Prevent admin from locking themselves out
        if ($staff_member->id === $request->user()->id && $validated['status'] === 'inactive') {
            return back()->withErrors(['status' => 'You cannot deactivate your own administrative account.']);
        }

        $oldData = $staff_member->only(['name', 'email', 'phone', 'status']);

        $staff_member->name = $validated['name'];
        $staff_member->email = $validated['email'];
        $staff_member->phone = $validated['phone'];
        $staff_member->username = $validated['username'] ?: null;
        $staff_member->status = $validated['status'];

        if (!empty($validated['password'])) {
            $staff_member->password = Hash::make($validated['password']);
        }

        $staff_member->save();

        // Update role if changed
        $currentRole = $staff_member->roles->first()?->slug;
        if ($currentRole !== $validated['role']) {
            $staff_member->roles()->detach();
            $staff_member->assignRole($validated['role']);
        }

        AuditLog::log('staff_updated', 'user', $staff_member, $oldData, $staff_member->only(['name', 'email', 'phone', 'status']));

        return redirect()->route('admin.staff-members.index')
            ->with('success', "Staff member '{$staff_member->name}' updated successfully.");
    }

    /**
     * Toggle the active/inactive status of a staff member.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('staff.update');

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['error' => 'You cannot change the status of your own account.']);
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        AuditLog::log('staff_status_toggled', 'user', $user, ['status' => $user->status], ['status' => $newStatus]);

        $statusText = $newStatus === 'active' ? 'Activated' : 'Deactivated';
        return redirect()->route('admin.staff-members.index')
            ->with('success', "Staff '{$user->name}' has been {$statusText}.");
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(Request $request, User $staff_member): RedirectResponse
    {
        Gate::authorize('staff.update');

        if ($staff_member->id === $request->user()->id) {
            return back()->withErrors(['error' => 'You cannot delete your own administrative account.']);
        }

        $name = $staff_member->name;
        $staff_member->roles()->detach();
        $staff_member->permissions()->detach();
        $staff_member->delete();

        AuditLog::log('staff_deleted', 'user', null, ['name' => $name], null);

        return redirect()->route('admin.staff-members.index')
            ->with('success', "Staff member '{$name}' removed successfully.");
    }
}
