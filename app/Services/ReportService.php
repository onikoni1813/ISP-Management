<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Renewal;
use App\Models\SalaryPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get Collection & Revenue Report.
     */
    public function getCollectionReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();
        $method = $filters['payment_method'] ?? null;
        $collectorId = $filters['collector_id'] ?? null;
        $accountId = $filters['account_id'] ?? null;
        $areaId = $filters['area_id'] ?? null;

        $query = Payment::with(['customer.area', 'account', 'collector'])
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($method) {
            $query->where('payment_method', $method);
        }

        if ($collectorId) {
            $query->where('collected_by', $collectorId);
        }

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        if ($areaId) {
            $query->whereHas('customer', fn($c) => $c->where('area_id', $areaId));
        }

        $totalAmount = (float) (clone $query)->sum('amount');
        $totalCount = (clone $query)->count();

        // Group by date for visual trend
        $dailyTotals = (clone $query)
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Group by payment method
        $byMethod = (clone $query)
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        $payments = $query->latest('paid_at')->paginate(20)->withQueryString();

        return [
            'summary' => [
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'daily_totals' => $dailyTotals,
            'by_method' => $byMethod,
            'payments' => $payments,
        ];
    }

    /**
     * Get Due & Outstanding Balances Report.
     */
    public function getDueReport(array $filters): array
    {
        $areaId = $filters['area_id'] ?? null;
        $search = $filters['search'] ?? null;

        // Invoices with due amount > 0 and status in [unpaid, partially_paid]
        $invoiceQuery = Invoice::with(['customer.area', 'customer.primaryContact'])
            ->where('due_amount', '>', 0)
            ->whereIn('status', ['unpaid', 'partially_paid']);

        if ($areaId) {
            $invoiceQuery->whereHas('customer', fn($q) => $q->where('area_id', $areaId));
        }

        if ($search) {
            $invoiceQuery->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%"));
            });
        }

        $totalDue = (float) (clone $invoiceQuery)->sum('due_amount');
        $totalInvoicesCount = (clone $invoiceQuery)->count();

        // Also aggregate customer balances
        $customerDueCount = Customer::where('balance', '<', 0)->count();
        $totalCustomerBalanceDue = abs((float) Customer::where('balance', '<', 0)->sum('balance'));

        $invoices = $invoiceQuery->orderBy('due_date', 'asc')->paginate(20)->withQueryString();

        return [
            'summary' => [
                'total_invoice_due' => $totalDue,
                'total_due_invoices' => $totalInvoicesCount,
                'total_due_customers' => $customerDueCount,
                'total_customer_balance_due' => $totalCustomerBalanceDue,
            ],
            'invoices' => $invoices,
        ];
    }

    /**
     * Get Renewals & Expiry Performance Report.
     */
    public function getRenewalReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();
        $packageId = $filters['package_id'] ?? null;

        $query = Renewal::with(['customer', 'package', 'connection'])
            ->whereBetween('renewed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($packageId) {
            $query->where('package_id', $packageId);
        }

        $totalRenewals = (clone $query)->count();
        $paidRenewalsCount = (clone $query)->where('is_zero_charge', false)->count();
        $zeroChargeRenewalsCount = (clone $query)->where('is_zero_charge', true)->count();
        $totalRevenue = (float) (clone $query)->sum('amount');

        $renewals = $query->latest('renewed_at')->paginate(20)->withQueryString();

        return [
            'summary' => [
                'total_renewals' => $totalRenewals,
                'paid_renewals_count' => $paidRenewalsCount,
                'zero_charge_renewals_count' => $zeroChargeRenewalsCount,
                'total_revenue' => $totalRevenue,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'renewals' => $renewals,
        ];
    }

    /**
     * Get Comprehensive Profit / Loss Statement (Rule 27).
     * Revenue - Operating Expenses - Salary = Net Profit.
     * Cash Balance vs Net Profit distinguished.
     */
    public function getProfitLossReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();

        // 1. Total Revenue from completed customer payments
        $revenue = (float) Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');

        // 2. Total Operational Expenses (posted status)
        $expenses = (float) Expense::where('status', 'posted')
            ->whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate)
            ->sum('amount');

        // Expense categorized breakdown
        $expenseByCategory = Expense::join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->where('expenses.status', 'posted')
            ->whereDate('expenses.expense_date', '>=', $startDate)
            ->whereDate('expenses.expense_date', '<=', $endDate)
            ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('expense_categories.name')
            ->get();

        // 3. Total Staff Salary Disbursed
        $salaries = (float) SalaryPayment::where('status', 'paid')
            ->whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<=', $endDate)
            ->sum('net_salary');

        // 4. Net Profit calculation
        $totalExpenditure = $expenses + $salaries;
        $netProfit = $revenue - $totalExpenditure;

        // 5. Distinct Current Liquid Cash/Bank Wallets balance
        $currentAccountsBalance = (float) Account::where('status', 'active')->sum('balance');
        $accountsBreakdown = Account::where('status', 'active')->get(['name', 'type', 'balance']);

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'financials' => [
                'total_revenue' => $revenue,
                'total_expenses' => $expenses,
                'total_salaries' => $salaries,
                'total_expenditure' => $totalExpenditure,
                'net_profit' => $netProfit,
                'current_liquid_balance' => $currentAccountsBalance,
            ],
            'expense_by_category' => $expenseByCategory,
            'accounts_breakdown' => $accountsBreakdown,
        ];
    }

    /**
     * Get Cash Flow and Account Movement Ledger.
     */
    public function getCashFlowReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();
        $accountId = $filters['account_id'] ?? null;
        $type = $filters['type'] ?? null;

        $query = AccountTransaction::with(['account', 'creator'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $totalInflow = (float) (clone $query)->sum('debit');
        $totalOutflow = (float) (clone $query)->sum('credit');
        $netFlow = $totalInflow - $totalOutflow;

        $transactions = $query->latest('id')->paginate(25)->withQueryString();

        return [
            'summary' => [
                'total_inflow' => $totalInflow,
                'total_outflow' => $totalOutflow,
                'net_flow' => $netFlow,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'transactions' => $transactions,
        ];
    }
}
