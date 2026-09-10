<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\SalaryPayment;
use App\Models\SalaryPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Account $cashAccount;
    protected Account $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->cashAccount = Account::create([
            'name' => 'Main Cash Drawer',
            'type' => 'Cash',
            'account_number' => 'CASH-001',
            'balance' => 50000.00,
            'status' => 'active',
        ]);

        $this->bankAccount = Account::create([
            'name' => 'City Bank Operations',
            'type' => 'Bank',
            'account_number' => '1102938481',
            'balance' => 100000.00,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_accounts_overview(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.accounting.accounts'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Accounting/Accounts'));
    }

    public function test_admin_can_transfer_funds_between_accounts(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.accounting.transfer'), [
            'from_account_id' => $this->cashAccount->id,
            'to_account_id' => $this->bankAccount->id,
            'amount' => 10000.00,
            'notes' => 'Daily cash deposit to bank',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check balances updated
        $this->assertEquals(40000.00, $this->cashAccount->fresh()->balance);
        $this->assertEquals(110000.00, $this->bankAccount->fresh()->balance);

        // Check 2 ledger transactions created
        $this->assertDatabaseHas('account_transactions', [
            'account_id' => $this->cashAccount->id,
            'type' => 'transfer_out',
            'credit' => 10000.00,
            'balance_after' => 40000.00,
        ]);

        $this->assertDatabaseHas('account_transactions', [
            'account_id' => $this->bankAccount->id,
            'type' => 'transfer_in',
            'debit' => 10000.00,
            'balance_after' => 110000.00,
        ]);
    }

    public function test_admin_can_record_operational_expense(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Fiber Optical Maintenance',
            'code' => 'FIBER_MAINT',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.accounting.expenses.store'), [
            'expense_category_id' => $category->id,
            'account_id' => $this->cashAccount->id,
            'amount' => 3500.00,
            'expense_date' => now()->toDateString(),
            'title' => 'Fiber joint enclosure replacement',
            'description' => 'Replaced joint box at Station Road',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check expense created
        $this->assertDatabaseHas('expenses', [
            'expense_category_id' => $category->id,
            'account_id' => $this->cashAccount->id,
            'amount' => 3500.00,
            'title' => 'Fiber joint enclosure replacement',
        ]);

        // Balance must be deducted
        $this->assertEquals(46500.00, $this->cashAccount->fresh()->balance);

        // Ledger transaction recorded
        $this->assertDatabaseHas('account_transactions', [
            'account_id' => $this->cashAccount->id,
            'type' => 'expense',
            'credit' => 3500.00,
            'balance_after' => 46500.00,
            'reference_type' => Expense::class,
        ]);
    }

    public function test_admin_can_process_salary_payout(): void
    {
        $staff = User::factory()->create(['name' => 'Lineman Faruk']);
        $period = SalaryPeriod::create([
            'name' => 'September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ]);

        // Net = 15000 + 2000 (bonus) - 1000 (advance) - 1000 (other) = 15000
        $response = $this->actingAs($this->admin)->post(route('admin.accounting.payroll.payout.store'), [
            'user_id' => $staff->id,
            'salary_period_id' => $period->id,
            'account_id' => $this->bankAccount->id,
            'basic_salary' => 15000.00,
            'bonus' => 2000.00,
            'commission' => 0.00,
            'advance_deduction' => 1000.00,
            'other_deductions' => 1000.00,
            'payment_date' => now()->toDateString(),
            'notes' => 'Salary disbursed via City Bank',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check salary payment record
        $this->assertDatabaseHas('salary_payments', [
            'user_id' => $staff->id,
            'salary_period_id' => $period->id,
            'basic_salary' => 15000.00,
            'net_salary' => 15000.00,
            'account_id' => $this->bankAccount->id,
        ]);

        // Bank balance reduced by 15000
        $this->assertEquals(85000.00, $this->bankAccount->fresh()->balance);

        // Ledger transaction recorded
        $this->assertDatabaseHas('account_transactions', [
            'account_id' => $this->bankAccount->id,
            'type' => 'salary',
            'credit' => 15000.00,
            'balance_after' => 85000.00,
            'reference_type' => SalaryPayment::class,
        ]);
    }
}
