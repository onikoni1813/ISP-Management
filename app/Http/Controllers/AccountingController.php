<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AccountTransfer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SalaryPayment;
use App\Models\SalaryPeriod;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AccountingController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * Accounts Overview & Balance.
     */
    public function accounts(): Response
    {
        Gate::authorize('accounts.view');

        $accounts = Account::withCount('payments')->get();
        $recentTransactions = AccountTransaction::with(['account', 'creator'])->latest('id')->take(20)->get();

        $today = now()->toDateString();
        $metrics = [
            'total_balance' => (float) $accounts->where('status', 'active')->sum('balance'),
            'active_accounts_count' => $accounts->where('status', 'active')->count(),
            'today_inflow' => (float) AccountTransaction::whereDate('created_at', $today)->sum('debit'),
            'today_outflow' => (float) AccountTransaction::whereDate('created_at', $today)->sum('credit'),
        ];

        return Inertia::render('Admin/Accounting/Accounts', [
            'accounts' => $accounts,
            'recentTransactions' => $recentTransactions,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Fund Transfer between Accounts.
     */
    public function transfer(Request $request)
    {
        Gate::authorize('accounts.transfer');

        $validated = $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->accountingService->transferFunds(
            $validated['from_account_id'],
            $validated['to_account_id'],
            (float) $validated['amount'],
            $request->user()->id,
            $validated['notes'] ?? null
        );

        return back()->with('success', 'Fund transfer completed successfully.');
    }

    /**
     * Expense Management Listing.
     */
    public function expenses(Request $request): Response
    {
        Gate::authorize('expenses.view');

        $categoryId = $request->input('category_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = $request->input('search');

        $expenses = Expense::with(['category', 'account', 'payer'])
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->when($dateFrom, fn($q) => $q->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('expense_date', '<=', $dateTo))
            ->when($search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $categories = ExpenseCategory::where('status', 'active')->get();
        $accounts = Account::where('status', 'active')->get();

        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();

        $metrics = [
            'total_expenses' => (float) Expense::sum('amount'),
            'this_month_expenses' => (float) Expense::whereDate('expense_date', '>=', $startOfMonth)->sum('amount'),
            'today_expenses' => (float) Expense::whereDate('expense_date', $today)->sum('amount'),
            'records_count' => Expense::count(),
        ];

        return Inertia::render('Admin/Accounting/Expenses', [
            'expenses' => $expenses,
            'categories' => $categories,
            'accounts' => $accounts,
            'metrics' => $metrics,
            'filters' => $request->only(['search', 'category_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Store Business Expense.
     */
    public function storeExpense(Request $request)
    {
        Gate::authorize('expenses.create');

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1',
            'title' => 'required|string|max:255',
            'expense_date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        $expense = $this->accountingService->recordExpense($validated, $request->user()->id);

        return back()->with('success', "Expense {$expense->expense_number} recorded successfully.");
    }

    /**
     * Staff Salary & Payroll Management.
     */
    public function payroll(Request $request): Response
    {
        Gate::authorize('staff.salary');

        $search = $request->input('search');
        $periodId = $request->input('period_id');
        $userId = $request->input('user_id');

        $payments = SalaryPayment::with(['user', 'salaryPeriod', 'account', 'payer'])
            ->when($periodId, fn($q) => $q->where('salary_period_id', $periodId))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('payroll_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest('payment_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $staffUsers = User::whereHas('roles', fn($r) => $r->whereIn('slug', ['staff', 'admin']))->get(['id', 'name']);
        $periods = SalaryPeriod::latest('id')->get();
        $accounts = Account::where('status', 'active')->get();

        $startOfMonth = now()->startOfMonth()->toDateString();
        $metrics = [
            'total_salary_disbursed' => (float) SalaryPayment::sum('net_salary'),
            'this_month_disbursed' => (float) SalaryPayment::whereDate('payment_date', '>=', $startOfMonth)->sum('net_salary'),
            'total_disbursements_count' => SalaryPayment::count(),
            'total_staff_count' => $staffUsers->count(),
        ];

        return Inertia::render('Admin/Accounting/Payroll', [
            'payments' => $payments,
            'staffUsers' => $staffUsers,
            'periods' => $periods,
            'accounts' => $accounts,
            'metrics' => $metrics,
            'filters' => $request->only(['search', 'period_id', 'user_id']),
        ]);
    }

    /**
     * Store Salary Payout.
     */
    public function storeSalary(Request $request)
    {
        Gate::authorize('staff.salary');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'salary_period_id' => 'required|exists:salary_periods,id',
            'account_id' => 'required|exists:accounts,id',
            'basic_salary' => 'required|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'commission' => 'nullable|numeric|min:0',
            'advance_deduction' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $salary = $this->accountingService->processSalaryPayout($validated, $request->user()->id);

        return back()->with('success', "Salary payout {$salary->payroll_number} of ৳{$salary->net_salary} processed successfully.");
    }

    /**
     * Store new Salary Period.
     */
    public function storePeriod(Request $request)
    {
        Gate::authorize('staff.salary');

        $validated = $request->validate([
            'period_name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        SalaryPeriod::create([
            'name' => $validated['period_name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'open',
        ]);

        return back()->with('success', "Salary period {$validated['period_name']} created successfully.");
    }

    /**
     * Create a new Account / Payment Method.
     */
    public function storeAccount(Request $request)
    {
        Gate::authorize('accounts.create');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:Mobile Banking,Bangla QR,Bank,Cash',
            'account_number' => 'nullable|string|max:100',
            'balance' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:active,inactive',
            'qr_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'qr_image_url' => 'nullable|string|max:500',
        ]);

        $qrPath = null;
        if ($request->hasFile('qr_image')) {
            $file = $request->file('qr_image');
            $filename = 'bangla_qr_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/qr'), $filename);
            $qrPath = '/uploads/qr/' . $filename;
        } elseif ($request->filled('qr_image_url')) {
            $qrPath = $request->input('qr_image_url');
        } elseif ($validated['type'] === 'Bangla QR') {
            $qrPath = '/uploads/qr/bangla_qr_merchant.svg';
        }

        $account = Account::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'account_number' => $validated['account_number'] ?? null,
            'qr_image' => $qrPath,
            'balance' => $validated['balance'] ?? 0.00,
            'status' => $validated['status'] ?? 'active',
        ]);

        return back()->with('success', "Account / Payment Method '{$account->name}' created successfully.");
    }

    /**
     * Update existing Account / Payment Method.
     */
    public function updateAccount(Request $request, Account $account)
    {
        Gate::authorize('accounts.create');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:Mobile Banking,Bangla QR,Bank,Cash',
            'account_number' => 'nullable|string|max:100',
            'status' => 'required|string|in:active,inactive',
            'qr_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'qr_image_url' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('qr_image')) {
            $file = $request->file('qr_image');
            $filename = 'bangla_qr_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/qr'), $filename);
            $validated['qr_image'] = '/uploads/qr/' . $filename;
        } elseif ($request->filled('qr_image_url')) {
            $validated['qr_image'] = $request->input('qr_image_url');
        }

        unset($validated['qr_image_url']);
        $account->update($validated);

        return back()->with('success', "Account '{$account->name}' updated successfully.");
    }

    /**
     * Toggle Account Active / Inactive Status.
     */
    public function toggleAccountStatus(Account $account)
    {
        Gate::authorize('accounts.create');

        $newStatus = $account->status === 'active' ? 'inactive' : 'active';
        $account->update(['status' => $newStatus]);

        return back()->with('success', "Account '{$account->name}' is now {$newStatus}.");
    }

    /**
     * Delete Account / Payment Method.
     */
    public function destroyAccount(Account $account)
    {
        Gate::authorize('accounts.create');

        // Check for existing transactions / collections to preserve financial integrity
        $hasTransactions = AccountTransaction::where('account_id', $account->id)->exists();
        $hasPayments = $account->payments()->exists();
        $hasExpenses = Expense::where('account_id', $account->id)->exists();

        if ($hasTransactions || $hasPayments || $hasExpenses) {
            return back()->withErrors([
                'error' => "Cannot delete '{$account->name}' because it has recorded financial transactions, collections, or expenses. You can deactivate it instead."
            ]);
        }

        $name = $account->name;
        $account->delete();

        return back()->with('success', "Account '{$name}' deleted successfully.");
    }
}
