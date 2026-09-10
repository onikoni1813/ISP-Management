<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AccountTransfer;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SalaryPayment;
use App\Models\SalaryPeriod;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Post a business operational expense.
     */
    public function recordExpense(array $data, int $userId): Expense
    {
        return DB::transaction(function () use ($data, $userId) {
            $account = Account::lockForUpdate()->findOrFail($data['account_id']);
            $amount = (float) $data['amount'];

            if ($account->balance < $amount) {
                // In cash accounting, balance warning or strict deduction
            }

            $lastExpense = Expense::lockForUpdate()->latest('id')->first();
            $nextNumber = $lastExpense ? ($lastExpense->id + 1) : 1;
            $expenseNumber = 'EXP-' . date('Y') . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

            $expense = Expense::create([
                'expense_number' => $expenseNumber,
                'expense_category_id' => $data['expense_category_id'],
                'account_id' => $account->id,
                'amount' => $amount,
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'paid_by' => $userId,
                'status' => 'posted',
            ]);

            // Deduct account balance
            $account->decrement('balance', $amount);

            // Record in Account Transaction Ledger
            AccountTransaction::create([
                'account_id' => $account->id,
                'transaction_number' => 'TXN-' . date('Y') . '-' . uniqid(),
                'type' => 'expense',
                'debit' => 0.00,
                'credit' => $amount,
                'balance_after' => $account->fresh()->balance,
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'description' => "Expense: {$expense->title}",
                'created_by' => $userId,
            ]);

            AuditLog::log('expense_created', 'accounting', $expense, null, $expense->toArray());

            return $expense;
        });
    }

    /**
     * Inter-account fund transfer (Cash to Bank, bKash to Cash, etc.).
     */
    public function transferFunds(int $fromAccountId, int $toAccountId, float $amount, int $userId, ?string $notes = null): AccountTransfer
    {
        if ($fromAccountId === $toAccountId) {
            throw new Exception('Source and destination accounts must be different.');
        }

        if ($amount <= 0) {
            throw new Exception('Transfer amount must be greater than zero.');
        }

        return DB::transaction(function () use ($fromAccountId, $toAccountId, $amount, $userId, $notes) {
            $fromAccount = Account::lockForUpdate()->findOrFail($fromAccountId);
            $toAccount = Account::lockForUpdate()->findOrFail($toAccountId);

            if ($fromAccount->balance < $amount) {
                throw new Exception("Insufficient balance in {$fromAccount->name} to transfer ৳{$amount}.");
            }

            $lastTransfer = AccountTransfer::lockForUpdate()->latest('id')->first();
            $nextNumber = $lastTransfer ? ($lastTransfer->id + 1) : 1;
            $transferNumber = 'TRF-' . date('Y') . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

            $transfer = AccountTransfer::create([
                'transfer_number' => $transferNumber,
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'amount' => $amount,
                'transfer_date' => now()->toDateString(),
                'notes' => $notes,
                'transferred_by' => $userId,
            ]);

            // Adjust account balances
            $fromAccount->decrement('balance', $amount);
            $toAccount->increment('balance', $amount);

            // Outflow ledger entry
            AccountTransaction::create([
                'account_id' => $fromAccount->id,
                'transaction_number' => 'TXN-' . date('Y') . '-' . uniqid(),
                'type' => 'transfer_out',
                'debit' => 0.00,
                'credit' => $amount,
                'balance_after' => $fromAccount->fresh()->balance,
                'reference_type' => AccountTransfer::class,
                'reference_id' => $transfer->id,
                'description' => "Transfer to {$toAccount->name}",
                'created_by' => $userId,
            ]);

            // Inflow ledger entry
            AccountTransaction::create([
                'account_id' => $toAccount->id,
                'transaction_number' => 'TXN-' . date('Y') . '-' . uniqid(),
                'type' => 'transfer_in',
                'debit' => $amount,
                'credit' => 0.00,
                'balance_after' => $toAccount->fresh()->balance,
                'reference_type' => AccountTransfer::class,
                'reference_id' => $transfer->id,
                'description' => "Transfer from {$fromAccount->name}",
                'created_by' => $userId,
            ]);

            AuditLog::log('account_transfer', 'accounting', $transfer, null, $transfer->toArray());

            return $transfer;
        });
    }

    /**
     * Process Period-based Staff Salary payout.
     */
    public function processSalaryPayout(array $data, int $paidBy): SalaryPayment
    {
        return DB::transaction(function () use ($data, $paidBy) {
            $account = Account::lockForUpdate()->findOrFail($data['account_id']);
            $staffUser = User::findOrFail($data['user_id']);
            $period = SalaryPeriod::findOrFail($data['salary_period_id']);

            $basic = (float) $data['basic_salary'];
            $bonus = (float) ($data['bonus'] ?? 0.00);
            $commission = (float) ($data['commission'] ?? 0.00);
            $advance = (float) ($data['advance_deduction'] ?? 0.00);
            $other = (float) ($data['other_deductions'] ?? 0.00);

            $netSalary = ($basic + $bonus + $commission) - ($advance + $other);
            if ($netSalary < 0) {
                throw new Exception('Net salary cannot be negative.');
            }

            $lastSalary = SalaryPayment::lockForUpdate()->latest('id')->first();
            $nextNumber = $lastSalary ? ($lastSalary->id + 1) : 1;
            $payrollNumber = 'PAYR-' . date('Y') . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

            $payment = SalaryPayment::create([
                'payroll_number' => $payrollNumber,
                'user_id' => $staffUser->id,
                'salary_period_id' => $period->id,
                'account_id' => $account->id,
                'basic_salary' => $basic,
                'bonus' => $bonus,
                'commission' => $commission,
                'advance_deduction' => $advance,
                'other_deductions' => $other,
                'net_salary' => $netSalary,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'status' => 'paid',
                'notes' => $data['notes'] ?? null,
                'paid_by' => $paidBy,
            ]);

            // Deduct from account
            $account->decrement('balance', $netSalary);

            // Ledger entry
            AccountTransaction::create([
                'account_id' => $account->id,
                'transaction_number' => 'TXN-' . date('Y') . '-' . uniqid(),
                'type' => 'salary',
                'debit' => 0.00,
                'credit' => $netSalary,
                'balance_after' => $account->fresh()->balance,
                'reference_type' => SalaryPayment::class,
                'reference_id' => $payment->id,
                'description' => "Salary payment to {$staffUser->name} for {$period->name}",
                'created_by' => $paidBy,
            ]);

            AuditLog::log('salary_paid', 'payroll', $payment, null, $payment->toArray());

            return $payment;
        });
    }
}
