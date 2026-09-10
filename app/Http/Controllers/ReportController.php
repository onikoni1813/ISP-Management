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

        return Inertia::render('Admin/Reports/Index');
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

        return Inertia::render('Admin/Reports/Collections', array_merge($reportData, [
            'collectors' => $collectors,
            'accounts' => $accounts,
            'filters' => $request->only(['start_date', 'end_date', 'payment_method', 'collector_id', 'account_id']),
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
