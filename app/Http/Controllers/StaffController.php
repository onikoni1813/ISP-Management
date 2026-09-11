<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Renewal;
use App\Services\CustomerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    /**
     * Mobile-first Staff Operations Dashboard.
     */
    public function dashboard(Request $request): Response
    {
        $userId = $request->user()->id;
        $today = Carbon::today();

        // Today's collections by this staff member
        $todayCollection = Payment::where('collected_by', $userId)
            ->whereDate('paid_at', $today)
            ->where('status', 'completed')
            ->sum('amount');

        $todayCollectionCount = Payment::where('collected_by', $userId)
            ->whereDate('paid_at', $today)
            ->where('status', 'completed')
            ->count();

        // Today's renewals by this staff member
        $todayRenewalsCount = Renewal::where('renewed_by', $userId)
            ->whereDate('renewed_at', $today)
            ->count();

        // Recent collections for quick review
        $recentCollections = Payment::with('customer')
            ->where('collected_by', $userId)
            ->latest('id')
            ->take(5)
            ->get();

        // Active assigned complaints count for this staff member
        $openComplaintsCount = Complaint::where('assigned_to', $userId)
            ->whereIn('status', ['open', 'assigned', 'in_progress'])
            ->count();

        return Inertia::render('Staff/Dashboard', [
            'metrics' => [
                'today_collection' => (float) $todayCollection,
                'today_collection_count' => $todayCollectionCount,
                'today_renewals_count' => $todayRenewalsCount,
                'open_complaints_count' => $openComplaintsCount,
            ],
            'recent_collections' => $recentCollections,
        ]);
    }

    /**
     * Staff instant customer live search API.
     */
    public function search(Request $request)
    {
        Gate::authorize('customers.view');

        $query = $request->input('q');
        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        $customers = Customer::with([
            'primaryContact',
            'connections.currentPackage',
            'connections.pppoeCredential',
        ])
        ->where('customer_code', 'like', "%{$query}%")
        ->orWhere('name', 'like', "%{$query}%")
        ->orWhereHas('contacts', fn($c) => $c->where('phone', 'like', "%{$query}%"))
        ->orWhereHas('connections.pppoeCredential', fn($p) => $p->where('username', 'like', "%{$query}%"))
        ->take(10)
        ->get();

        return response()->json($customers);
    }

    /**
     * Staff fast single customer view with quick collect & renew actions.
     */
    public function customerDetails(Customer $customer): Response
    {
        Gate::authorize('customers.view');

        $customer->load([
            'area',
            'primaryContact',
            'installationAddress',
            'connections.currentPackage.currentPrice',
            'connections.pppoeCredential',
            'packageHistories.package',
        ]);

        return Inertia::render('Staff/CustomerDetails', [
            'customer' => $customer,
            'canViewPppoePassword' => Gate::allows('pppoe.view_password'),
        ]);
    }
}
