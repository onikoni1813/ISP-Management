<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Renewal;
use App\Models\Role;
use App\Models\SalaryPayment;
use App\Models\SalaryPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->account = Account::create([
            'name' => 'Main Cash Drawer',
            'type' => 'Cash',
            'account_number' => 'CASH-001',
            'balance' => 50000.00,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_reports_index_hub(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Reports/Index'));
    }

    public function test_admin_can_view_collections_report(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-000101',
            'name' => 'Kabila Hasan',
            'status' => 'active',
            'join_date' => now()->toDateString(),
            'balance' => 0.00,
        ]);

        Payment::create([
            'payment_number' => 'PAY-2026-0001',
            'customer_id' => $customer->id,
            'account_id' => $this->account->id,
            'amount' => 1500.00,
            'payment_method' => 'bKash',
            'status' => 'completed',
            'paid_at' => now(),
            'collected_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.collections', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Reports/Collections')
            ->has('summary')
            ->where('summary.total_amount', 1500)
            ->where('summary.total_count', 1)
        );
    }

    public function test_admin_can_view_dues_report(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-000102',
            'name' => 'Ruhul Amin',
            'status' => 'active',
            'join_date' => now()->toDateString(),
            'balance' => -800.00,
        ]);

        Invoice::create([
            'invoice_number' => 'INV-2026-0001',
            'customer_id' => $customer->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'subtotal' => 1000.00,
            'total' => 1000.00,
            'paid_amount' => 200.00,
            'due_amount' => 800.00,
            'status' => 'partially_paid',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.dues'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Reports/Dues')
            ->where('summary.total_invoice_due', 800)
            ->where('summary.total_due_invoices', 1)
            ->where('summary.total_customer_balance_due', 800)
        );
    }

    public function test_admin_can_view_profit_loss_financial_statement_rule_27(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-000103',
            'name' => 'Shahidul Islam',
            'status' => 'active',
            'join_date' => now()->toDateString(),
            'balance' => 0.00,
        ]);

        // 1. Revenue = 20,000
        Payment::create([
            'payment_number' => 'PAY-2026-0001',
            'customer_id' => $customer->id,
            'account_id' => $this->account->id,
            'amount' => 20000.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
            'paid_at' => now(),
            'collected_by' => $this->admin->id,
        ]);

        // 2. Expense = 5,000
        $category = ExpenseCategory::create(['name' => 'Bandwidth Cost', 'code' => 'BANDWIDTH']);
        Expense::create([
            'expense_number' => 'EXP-2026-0001',
            'expense_category_id' => $category->id,
            'account_id' => $this->account->id,
            'amount' => 5000.00,
            'expense_date' => now()->toDateString(),
            'title' => 'Upstream Bandwidth',
            'paid_by' => $this->admin->id,
            'status' => 'posted',
        ]);

        // 3. Salary = 7,000
        $period = SalaryPeriod::create([
            'name' => 'Current Period',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]);
        SalaryPayment::create([
            'payroll_number' => 'PAYR-2026-0001',
            'user_id' => $this->admin->id,
            'salary_period_id' => $period->id,
            'account_id' => $this->account->id,
            'basic_salary' => 7000.00,
            'net_salary' => 7000.00,
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
            'paid_by' => $this->admin->id,
        ]);

        // Net Profit = 20,000 - 5,000 - 7,000 = 8,000
        $response = $this->actingAs($this->admin)->get(route('admin.reports.profit-loss', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Reports/ProfitLoss')
            ->where('financials.total_revenue', 20000)
            ->where('financials.total_expenses', 5000)
            ->where('financials.total_salaries', 7000)
            ->where('financials.total_expenditure', 12000)
            ->where('financials.net_profit', 8000)
            ->where('financials.current_liquid_balance', 50000)
        );
    }
}
