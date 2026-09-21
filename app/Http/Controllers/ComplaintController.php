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
    public function index(Request $request): Response
    {
        Gate::authorize('complaints.view');

        $user = $request->user();
        $isStaffOnly = $user->hasRole('staff') && !$user->hasRole('admin');

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

        return Inertia::render('Admin/Complaints/Index', [
            'complaints' => $complaints,
            'filters' => $request->only(['search', 'status', 'priority']),
            'counts' => $counts,
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
    public function store(Request $request, Customer $customer)
    {
        Gate::authorize('complaints.create');

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|string|in:low,normal,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'connection_id' => 'nullable|exists:connections,id',
        ]);

        $complaint = $this->complaintService->createComplaint($customer, $validated, $request->user()->id);

        return back()->with('success', "Complaint {$complaint->complaint_number} logged successfully.");
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
}
