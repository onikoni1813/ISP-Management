<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Renewal;
use App\Models\StaffHandover;
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
        $now = Carbon::now();
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $weekStart = Carbon::now()->startOfWeek()->startOfDay();
        $weekEnd = Carbon::now()->endOfWeek()->endOfDay();
        $monthStart = Carbon::now()->startOfMonth()->startOfDay();
        $monthEnd = Carbon::now()->endOfMonth()->endOfDay();

        // Base query for completed collections by this staff
        $baseQuery = fn() => Payment::where('collected_by', $userId)->where('status', 'completed');

        // Today metrics
        $todayCollection = (float) $baseQuery()->whereBetween('paid_at', [$todayStart, $todayEnd])->sum('amount');
        $todayCollectionCount = $baseQuery()->whereBetween('paid_at', [$todayStart, $todayEnd])->count();
        $todayRenewalsCount = Renewal::where('renewed_by', $userId)->whereBetween('renewed_at', [$todayStart, $todayEnd])->count();
        $todayCash = (float) $baseQuery()->whereBetween('paid_at', [$todayStart, $todayEnd])->where('payment_method', 'cash')->sum('amount');
        $todayDigital = (float) $baseQuery()->whereBetween('paid_at', [$todayStart, $todayEnd])->whereIn('payment_method', ['bkash', 'nagad', 'rocket', 'bank', 'online'])->sum('amount');

        // Weekly metrics
        $weekCollection = (float) $baseQuery()->whereBetween('paid_at', [$weekStart, $weekEnd])->sum('amount');
        $weekCollectionCount = $baseQuery()->whereBetween('paid_at', [$weekStart, $weekEnd])->count();
        $weekRenewalsCount = Renewal::where('renewed_by', $userId)->whereBetween('renewed_at', [$weekStart, $weekEnd])->count();
        $weekCash = (float) $baseQuery()->whereBetween('paid_at', [$weekStart, $weekEnd])->where('payment_method', 'cash')->sum('amount');
        $weekDigital = (float) $baseQuery()->whereBetween('paid_at', [$weekStart, $weekEnd])->whereIn('payment_method', ['bkash', 'nagad', 'rocket', 'bank', 'online'])->sum('amount');

        // Monthly metrics
        $monthCollection = (float) $baseQuery()->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount');
        $monthCollectionCount = $baseQuery()->whereBetween('paid_at', [$monthStart, $monthEnd])->count();
        $monthRenewalsCount = Renewal::where('renewed_by', $userId)->whereBetween('renewed_at', [$monthStart, $monthEnd])->count();
        $monthCash = (float) $baseQuery()->whereBetween('paid_at', [$monthStart, $monthEnd])->where('payment_method', 'cash')->sum('amount');
        $monthDigital = (float) $baseQuery()->whereBetween('paid_at', [$monthStart, $monthEnd])->whereIn('payment_method', ['bkash', 'nagad', 'rocket', 'bank', 'online'])->sum('amount');

        // Yearly metrics
        $yearStart = Carbon::now()->startOfYear()->startOfDay();
        $yearEnd = Carbon::now()->endOfYear()->endOfDay();
        $yearCollection = (float) $baseQuery()->whereBetween('paid_at', [$yearStart, $yearEnd])->sum('amount');
        $yearCollectionCount = $baseQuery()->whereBetween('paid_at', [$yearStart, $yearEnd])->count();
        $yearRenewalsCount = Renewal::where('renewed_by', $userId)->whereBetween('renewed_at', [$yearStart, $yearEnd])->count();
        $yearCash = (float) $baseQuery()->whereBetween('paid_at', [$yearStart, $yearEnd])->where('payment_method', 'cash')->sum('amount');
        $yearDigital = (float) $baseQuery()->whereBetween('paid_at', [$yearStart, $yearEnd])->whereIn('payment_method', ['bkash', 'nagad', 'rocket', 'bank', 'online'])->sum('amount');

        // Recent collections for quick review & handover
        $recentCollections = Payment::with('customer')
            ->where('collected_by', $userId)
            ->where('status', 'completed')
            ->latest('paid_at')
            ->take(15)
            ->get();

        // Active assigned complaints for this staff member
        $openComplaints = Complaint::with([
            'customer.primaryContact',
            'customer.installationAddress',
            'customer.connections.currentPackage',
            'creator',
        ])
            ->where('assigned_to', $userId)
            ->whereIn('status', ['open', 'assigned', 'in_progress'])
            ->latest('id')
            ->get();
            
        $openComplaintsCount = $openComplaints->count();

        $areas = Area::select('id', 'name')->orderBy('name')->get();

        // Auto-reconcile / upsert backend StaffHandover ledger for today
        $todayDateString = Carbon::today()->toDateString();
        $todayHandover = null;

        if ($todayCollectionCount > 0) {
            $todayHandover = StaffHandover::firstOrCreate(
                [
                    'staff_id' => $userId,
                    'handover_date' => $todayDateString,
                ],
                [
                    'handover_number' => 'HND-' . Carbon::today()->format('Ymd') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT),
                    'status' => 'pending',
                ]
            );

            // Auto-update amounts if not yet verified by admin
            if ($todayHandover->status === 'pending') {
                $todayHandover->update([
                    'cash_amount' => $todayCash,
                    'digital_amount' => $todayDigital,
                    'total_amount' => $todayCollection,
                    'collections_count' => $todayCollectionCount,
                ]);
            }
        } else {
            $todayHandover = StaffHandover::where('staff_id', $userId)
                ->where('handover_date', $todayDateString)
                ->first();
        }

        return Inertia::render('Staff/Dashboard', [
            'metrics' => [
                'today_collection' => $todayCollection,
                'today_collection_count' => $todayCollectionCount,
                'today_renewals_count' => $todayRenewalsCount,
                'today_cash' => $todayCash,
                'today_digital' => $todayDigital,

                'week_collection' => $weekCollection,
                'week_collection_count' => $weekCollectionCount,
                'week_renewals_count' => $weekRenewalsCount,
                'week_cash' => $weekCash,
                'week_digital' => $weekDigital,

                'month_collection' => $monthCollection,
                'month_collection_count' => $monthCollectionCount,
                'month_renewals_count' => $monthRenewalsCount,
                'month_cash' => $monthCash,
                'month_digital' => $monthDigital,

                'year_collection' => $yearCollection,
                'year_collection_count' => $yearCollectionCount,
                'year_renewals_count' => $yearRenewalsCount,
                'year_cash' => $yearCash,
                'year_digital' => $yearDigital,

                'open_complaints_count' => $openComplaintsCount,
            ],
            'today_handover' => $todayHandover,
            'recent_collections' => $recentCollections,
            'open_complaints' => $openComplaints,
            'areas' => $areas,
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
     * Staff filtered customers for dashboard operational tabs.
     */
    public function filteredCustomers(Request $request)
    {
        Gate::authorize('customers.view');
        
        $statusFilter = $request->input('filter', 'all'); // 'all', 'due', 'paid', 'renewed', 'expiring_72h', 'expired'
        $areaId = $request->input('area_id');
        $now = Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth();
        $in72Hours = Carbon::now()->addHours(72);

        // Base query with relations
        $baseQuery = Customer::with([
            'primaryContact',
            'connections.currentPackage.currentPrice',
            'connections.pppoeCredential',
        ]);

        if ($areaId) {
            $baseQuery->where('area_id', $areaId);
        }

        // 1. Calculate live counts for all operational filter badges
        $countsQuery = clone $baseQuery;
        
        $dueCount = (clone $countsQuery)
            ->where(function ($q) {
                $q->where('balance', '<', 0)
                  ->orWhereHas('connections', fn($c) => $c->where('expiry_date', '<', Carbon::today()));
            })->count();

        $paidCount = (clone $countsQuery)
            ->where('balance', '>=', 0)
            ->whereHas('payments', function ($p) use ($startOfMonth) {
                $p->where('paid_at', '>=', $startOfMonth);
            })->count();

        $renewedCount = (clone $countsQuery)
            ->whereHas('connections', function ($c) use ($startOfMonth) {
                $c->whereHas('packageHistories', fn($h) => $h->where('start_date', '>=', $startOfMonth->toDateString()));
            })->count();

        $expiring72hCount = (clone $countsQuery)
            ->whereHas('connections', function ($c) use ($now, $in72Hours) {
                $c->whereBetween('expiry_date', [$now->toDateString(), $in72Hours->toDateString()]);
            })->count();

        $expiredCount = (clone $countsQuery)
            ->whereHas('connections', function ($c) use ($now) {
                $c->where('expiry_date', '<', $now->toDateString());
            })->count();

        // 2. Apply chosen operational filter
        $query = clone $baseQuery;

        switch ($statusFilter) {
            case 'due': // আদায় বাকি
                $query->where(function ($q) {
                    $q->where('balance', '<', 0)
                      ->orWhereHas('connections', fn($c) => $c->where('expiry_date', '<', Carbon::today()));
                });
                break;

            case 'paid': // বিল জমা
                $query->where('balance', '>=', 0)
                      ->whereHas('payments', function ($p) use ($startOfMonth) {
                          $p->where('paid_at', '>=', $startOfMonth);
                      });
                break;

            case 'renewed': // রিনিউ হয়েছে
                $query->whereHas('connections', function ($c) use ($startOfMonth) {
                    $c->whereHas('packageHistories', fn($h) => $h->where('start_date', '>=', $startOfMonth->toDateString()));
                });
                break;

            case 'expiring_72h': // আগামী ৭২ ঘণ্টার মধ্যে মেয়াদ শেষ
                $query->whereHas('connections', function ($c) use ($now, $in72Hours) {
                    $c->whereBetween('expiry_date', [$now->toDateString(), $in72Hours->toDateString()]);
                });
                break;

            case 'expired': // মেয়াদ উত্তীর্ণ
                $query->whereHas('connections', function ($c) use ($now) {
                    $c->where('expiry_date', '<', $now->toDateString());
                });
                break;

            case 'all':
            default:
                // Return active customer list
                $query->where('status', 'active');
                break;
        }

        $customers = $query->take(80)->get();

        return response()->json([
            'customers' => $customers,
            'counts' => [
                'all' => (clone $countsQuery)->count(),
                'due' => $dueCount,
                'paid' => $paidCount,
                'renewed' => $renewedCount,
                'expiring_72h' => $expiring72hCount,
                'expired' => $expiredCount,
            ]
        ]);
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
            'customerNotes.author',
        ]);

        return Inertia::render('Staff/CustomerDetails', [
            'customer' => $customer,
            'canViewPppoePassword' => Gate::allows('pppoe.view_password'),
        ]);
    }

    /**
     * API for live background polling of staff assigned complaints and notifications.
     */
    public function liveCounts(Request $request)
    {
        $userId = $request->user()->id;

        $openComplaints = Complaint::with(['customer.primaryContact'])
            ->where('assigned_to', $userId)
            ->whereIn('status', ['open', 'assigned', 'in_progress'])
            ->latest('id')
            ->get();

        return response()->json([
            'open_complaints_count' => $openComplaints->count(),
            'latest_complaint' => $openComplaints->first(),
        ]);
    }
}
