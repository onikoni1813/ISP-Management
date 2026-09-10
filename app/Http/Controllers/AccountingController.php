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

        return Inertia::render('Admin/Accounting/Accounts', [
            'accounts' => $accounts,
            'recentTransactions' => $recentTransactions,
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

        $expenses = Expense::with(['category', 'account', 'payer'])
            ->when($request->category_id, fn($q) => $q->where('expense_category_id', $request->category_id))
            ->when($request->search, function ($q, $search) {
                $q->where('expense_number', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%");
            })
            ->latest('expense_date')
            ->paginate(15);

        $categories = ExpenseCategory::where('status', 'active')->get();
        $accounts = Account::where('status', 'active')->get();

        return Inertia::render('Admin/Accounting/Expenses', [
            'expenses' => $expenses,
            'categories' => $categories,
            'accounts' => $accounts,
            'filters' => $request->only(['search', 'category_id']),
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

        $payments = SalaryPayment::with(['user', 'salaryPeriod', 'account', 'payer'])
            ->latest('payment_date')
            ->paginate(15);

        $staffUsers = User::whereHas('roles', fn($r) => $r->whereIn('slug', ['staff', 'admin']))->get(['id', 'name']);
        $periods = SalaryPeriod::latest('id')->get();
        $accounts = Account::where('status', 'active')->get();

        return Inertia::render('Admin/Accounting/Payroll', [
            'payments' => $payments,
            'staffUsers' => $staffUsers,
            'periods' => $periods,
            'accounts' => $accounts,
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
}
