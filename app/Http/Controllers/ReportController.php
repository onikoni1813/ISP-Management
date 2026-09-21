<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Area;
use App\Models\Package;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Reports Hub Index.
     */
    public function index(): Response
    {
        Gate::authorize('reports.view');

        $thisMonth = now()->month;
        $thisYear = now()->year;

        $metrics = [
            'monthly_revenue' => (float) \App\Models\Payment::where('status', 'completed')
                ->whereMonth('paid_at', $thisMonth)
                ->whereYear('paid_at', $thisYear)
                ->sum('amount'),
            'total_due' => (float) \App\Models\Invoice::where('due_amount', '>', 0)
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->sum('due_amount'),
            'monthly_expenses' => (float) \App\Models\Expense::where('status', 'posted')
                ->whereMonth('expense_date', $thisMonth)
                ->whereYear('expense_date', $thisYear)
                ->sum('amount'),
            'monthly_salaries' => (float) \App\Models\SalaryPayment::where('status', 'paid')
                ->whereMonth('payment_date', $thisMonth)
                ->whereYear('payment_date', $thisYear)
                ->sum('net_salary'),
            'active_accounts_balance' => (float) \App\Models\Account::where('status', 'active')->sum('balance'),
        ];

        return Inertia::render('Admin/Reports/Index', [
            'metrics' => $metrics,
        ]);
    }

    /**
     * Collection & Revenue Report.
     */
    public function collections(Request $request): Response
    {
        Gate::authorize('reports.view');

        $reportData = $this->reportService->getCollectionReport($request->all());

        $collectors = User::whereHas('roles', fn($r) => $r->whereIn('slug', ['admin', 'staff']))->get(['id', 'name']);
        $accounts = Account::where('status', 'active')->get(['id', 'name', 'type']);
        $areas = Area::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Reports/Collections', array_merge($reportData, [
            'collectors' => $collectors,
            'accounts' => $accounts,
            'areas' => $areas,
            'filters' => $request->only(['start_date', 'end_date', 'payment_method', 'collector_id', 'account_id', 'area_id']),
        ]));
    }

    /**
     * Due & Outstanding Balances Report.
     */
    public function dues(Request $request): Response
    {
        Gate::authorize('reports.view');

        $reportData = $this->reportService->getDueReport($request->all());
        $areas = Area::where('status', 'active')->get(['id', 'name']);

        return Inertia::render('Admin/Reports/Dues', array_merge($reportData, [
            'areas' => $areas,
            'filters' => $request->only(['area_id', 'search']),
        ]));
    }

    /**
     * Renewals & Expiry Performance Report.
     */
    public function renewals(Request $request): Response
    {
        Gate::authorize('reports.view');

        $reportData = $this->reportService->getRenewalReport($request->all());
        $packages = Package::where('is_active', true)->get(['id', 'name', 'speed_mbps']);

        return Inertia::render('Admin/Reports/Renewals', array_merge($reportData, [
            'packages' => $packages,
            'filters' => $request->only(['start_date', 'end_date', 'package_id']),
        ]));
    }

    /**
     * Profit & Loss Financial Statement (Rule 27).
     */
    public function profitLoss(Request $request): Response
    {
        Gate::authorize('reports.view');

        $reportData = $this->reportService->getProfitLossReport($request->all());

        return Inertia::render('Admin/Reports/ProfitLoss', array_merge($reportData, [
            'filters' => $request->only(['start_date', 'end_date']),
        ]));
    }

    /**
     * Cash Flow & Account Ledger Audit.
     */
    public function cashFlow(Request $request): Response
    {
        Gate::authorize('reports.view');

        $reportData = $this->reportService->getCashFlowReport($request->all());
        $accounts = Account::where('status', 'active')->get(['id', 'name', 'type']);

        return Inertia::render('Admin/Reports/CashFlow', array_merge($reportData, [
            'accounts' => $accounts,
            'filters' => $request->only(['start_date', 'end_date', 'account_id', 'type']),
        ]));
    }
}
