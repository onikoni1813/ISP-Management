<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\User;
use App\Services\ComplaintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService
    ) {}

    /**
     * Display complaints list.
     */
    public function index(Request $request)
    {
        Gate::authorize('complaints.view');

        $user = $request->user();
        $isStaffOnly = $user->hasRole('staff') && !$user->hasRole('admin');

        if ($isStaffOnly || $request->routeIs('staff.*')) {
            return redirect()->route('staff.dashboard', ['tab' => 'complaints']);
        }

        $query = Complaint::with(['customer.primaryContact', 'assignee', 'creator'])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->priority, fn($q, $priority) => $q->where('priority', $priority))
            ->when($isStaffOnly, fn($q) => $q->where('assigned_to', $user->id))
            ->when($request->search, function ($q, $search) {
                $q->where('complaint_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%"));
            });

        $complaints = $query->latest('id')->paginate(15)->withQueryString();

        $countsQuery = Complaint::query()
            ->when($isStaffOnly, fn($q) => $q->where('assigned_to', $user->id));

        $counts = [
            'total' => (clone $countsQuery)->count(),
            'open' => (clone $countsQuery)->where('status', 'open')->count(),
            'in_progress' => (clone $countsQuery)->whereIn('status', ['assigned', 'in_progress'])->count(),
            'resolved' => (clone $countsQuery)->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        $staffUsers = User::whereHas('roles', fn($r) => $r->whereIn('slug', ['staff', 'admin']))
            ->where('status', 'active')
            ->get(['id', 'name']);

        $customersList = Customer::where('status', '!=', 'archived')
            ->with(['primaryContact', 'connections'])
            ->get(['id', 'customer_code', 'name'])
            ->map(fn($c) => [
                'id' => $c->id,
                'customer_code' => $c->customer_code,
                'name' => $c->name,
                'phone' => $c->primaryContact?->phone ?? '',
                'connection_id' => $c->connections->first()?->id,
            ]);

        return Inertia::render('Admin/Complaints/Index', [
            'complaints' => $complaints,
            'filters' => $request->only(['search', 'status', 'priority']),
            'counts' => $counts,
            'staffUsers' => $staffUsers,
            'customersList' => $customersList,
        ]);
    }

    /**
     * Show complaint ticket detail.
     */
    public function show(Request $request, Complaint $complaint): Response
    {
        Gate::authorize('complaints.view');

        $complaint->load([
            'customer.primaryContact',
            'customer.installationAddress',
            'connection.currentPackage',
            'connection.pppoeCredential',
            'assignee',
            'creator',
            'resolver',
            'comments.user',
            'statusHistories.changer',
        ]);

        $isStaffRoute = $request->routeIs('staff.*') || ($request->user()?->hasRole('staff') && !$request->user()?->hasRole('admin'));

        if ($isStaffRoute) {
            return Inertia::render('Staff/ComplaintDetails', [
                'complaint' => $complaint,
            ]);
        }

        $staffUsers = User::whereHas('roles', fn($r) => $r->whereIn('slug', ['staff', 'admin']))->get(['id', 'name']);

        return Inertia::render('Admin/Complaints/Show', [
            'complaint' => $complaint,
            'staffUsers' => $staffUsers,
        ]);
    }

    /**
     * Store new complaint ticket.
     */
    public function store(Request $request, ?Customer $customer = null)
    {
        Gate::authorize('complaints.create');

        $validated = $request->validate([
            'customer_id' => $customer ? 'nullable|exists:customers,id' : 'required|exists:customers,id',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'priority' => 'required|string|in:low,normal,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'connection_id' => 'nullable|exists:connections,id',
        ]);

        $targetCustomer = $customer ?: Customer::findOrFail($validated['customer_id']);

        $complaint = $this->complaintService->createComplaint($targetCustomer, $validated, $request->user()->id);

        return back()->with('success', "কমপ্লেইন টিকেট #{$complaint->complaint_number} সফলভাবে তৈরি হয়েছে।");
    }

    /**
     * Assign staff to ticket.
     */
    public function assign(Request $request, Complaint $complaint)
    {
        Gate::authorize('complaints.assign');

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $this->complaintService->assignComplaint($complaint, $validated['assigned_to'], $request->user()->id);

        return back()->with('success', 'Complaint assigned to technician.');
    }

    /**
     * Update complaint status (In progress, Resolved, etc.).
     */
    public function updateStatus(Request $request, Complaint $complaint)
    {
        Gate::authorize('complaints.update');

        $validated = $request->validate([
            'status' => 'required|string|in:open,assigned,in_progress,resolved,closed,cancelled',
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        $this->complaintService->updateStatus($complaint, $validated['status'], $request->user()->id, $validated['resolution_note'] ?? null);

        return back()->with('success', "Ticket status updated to {$validated['status']}.");
    }

    /**
     * Post comment on ticket.
     */
    public function addComment(Request $request, Complaint $complaint)
    {
        Gate::authorize('complaints.update');

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $this->complaintService->addComment($complaint, $validated['comment'], $request->user()->id);

        return back()->with('success', 'Comment added.');
    }

    /**
     * Delete an individual complaint ticket.
     */
    public function destroy(Request $request, Complaint $complaint)
    {
        if (!$request->user()->hasRole('admin')) {
            abort(403, 'Only administrators can delete complaint records.');
        }

        $ticketNo = $complaint->complaint_number;
        $this->complaintService->deleteComplaint($complaint);

        if ($request->header('X-Inertia-Location') || $request->routeIs('admin.complaints.show')) {
            return redirect()->route('admin.complaints.index')->with('success', "Ticket {$ticketNo} deleted successfully.");
        }

        return back()->with('success', "Ticket {$ticketNo} deleted successfully.");
    }

    /**
     * Bulk delete selected complaints.
     */
    public function bulkDestroy(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            abort(403, 'Only administrators can delete complaint records.');
        }

        $validated = $request->validate([
            'complaint_ids' => 'required|array|min:1',
            'complaint_ids.*' => 'integer|exists:complaints,id',
        ]);

        $count = $this->complaintService->bulkDeleteComplaints($validated['complaint_ids']);

        return back()->with('success', "Selected {$count} ticket(s) deleted successfully.");
    }

    /**
     * Clear complaint history.
     */
    public function clearHistory(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            abort(403, 'Only administrators can clear complaint history.');
        }

        $validated = $request->validate([
            'filter_type' => 'required|string|in:resolved,older_than_30_days,older_than_90_days,all',
        ]);

        $count = $this->complaintService->clearHistory($validated['filter_type']);

        return back()->with('success', "Cleared {$count} complaint record(s) from history.");
    }
}
