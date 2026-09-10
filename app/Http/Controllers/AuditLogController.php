<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Display Global Audit Trail Ledger with multi-dimensional filtering.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('audit.view');

        $query = AuditLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('entity_type', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $logs = $query->latest('id')->paginate(20)->withQueryString();

        $staffUsers = User::whereHas('roles', fn($r) => $r->whereIn('slug', ['admin', 'staff']))
            ->get(['id', 'name', 'email']);

        $modules = AuditLog::select('module')->distinct()->pluck('module');
        $actions = AuditLog::select('action')->distinct()->pluck('action');

        return Inertia::render('Admin/Audit/Index', [
            'logs' => $logs,
            'staffUsers' => $staffUsers,
            'modules' => $modules,
            'actions' => $actions,
            'filters' => $request->only(['user_id', 'module', 'action', 'start_date', 'end_date', 'search']),
        ]);
    }

    /**
     * Display Staff Activity & Accountability Report.
     */
    public function staffReport(Request $request): Response
    {
        Gate::authorize('audit.view');

        $staffId = $request->input('staff_id');
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        // Get staff list
        $staffUsers = User::whereHas('roles', fn($r) => $r->whereIn('slug', ['staff', 'admin']))->get(['id', 'name', 'email']);

        $stats = null;
        $collections = [];

        if ($staffId) {
            $selectedStaff = User::findOrFail($staffId);

            $collectionsQuery = Payment::with('customer')
                ->where('collected_by', $staffId)
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

            $totalAmount = (float) $collectionsQuery->sum('amount');
            $totalCount = $collectionsQuery->count();
            $collections = $collectionsQuery->latest('paid_at')->take(50)->get();

            // Distinct customers serviced
            $uniqueCustomersCount = $collectionsQuery->distinct('customer_id')->count('customer_id');

            // Audited Actions count by this staff
            $actionsCount = AuditLog::where('user_id', $staffId)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->count();

            $stats = [
                'staff_name' => $selectedStaff->name,
                'total_collections_amount' => $totalAmount,
                'total_collections_count' => $totalCount,
                'unique_customers_count' => $uniqueCustomersCount,
                'total_audited_actions' => $actionsCount,
            ];
        }

        return Inertia::render('Admin/Audit/StaffReport', [
            'staffUsers' => $staffUsers,
            'stats' => $stats,
            'collections' => $collections,
            'filters' => [
                'staff_id' => $staffId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
