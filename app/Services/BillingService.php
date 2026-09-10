<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Exception;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Create an Invoice for a customer.
     */
    public function createInvoice(Customer $customer, array $data, int $userId): Invoice
    {
        return DB::transaction(function () use ($customer, $data, $userId) {
            $lastInvoice = Invoice::lockForUpdate()->latest('id')->first();
            $nextNumber = $lastInvoice ? ($lastInvoice->id + 1) : 1;
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

            $subtotal = 0.00;
            $itemsData = $data['items'] ?? [];

            foreach ($itemsData as $item) {
                $subtotal += ($item['unit_price'] * ($item['quantity'] ?? 1));
            }

            $discount = (float) ($data['discount'] ?? 0.00);
            $total = max(0, $subtotal - $discount);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'billing_cycle_id' => $data['billing_cycle_id'] ?? null,
                'period_start' => $data['period_start'] ?? now()->startOfMonth()->toDateString(),
                'period_end' => $data['period_end'] ?? now()->endOfMonth()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->addDays(7)->toDateString(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => 0.00,
                'total' => $total,
                'paid_amount' => 0.00,
                'due_amount' => $total,
                'status' => 'unpaid',
                'created_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($itemsData as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => $item['item_type'] ?? 'package',
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'] ?? 1,
                    'total' => ($item['unit_price'] * ($item['quantity'] ?? 1)),
                ]);
            }

            // Update customer balance
            $customer->increment('balance', $total);

            AuditLog::log('invoice_created', 'billing', $invoice, null, $invoice->toArray());

            return $invoice;
        });
    }

    /**
     * Process a Payment with idempotent protection and multi-invoice allocation.
     */
    public function collectPayment(Customer $customer, array $data, int $userId): Payment
    {
        // 1. Idempotency Check: Return existing payment if duplicate request
        if (!empty($data['idempotency_key'])) {
            $existing = Payment::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($customer, $data, $userId) {
            $lastPayment = Payment::lockForUpdate()->latest('id')->first();
            $nextNumber = $lastPayment ? ($lastPayment->id + 1) : 1;
            $paymentNumber = 'PAY-' . date('Y') . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw new Exception('Payment amount must be greater than zero.');
            }

            // Target Account
            $account = !empty($data['account_id']) 
                ? Account::lockForUpdate()->findOrFail($data['account_id'])
                : Account::firstOrCreate(['name' => 'Cash in Hand'], ['type' => 'cash', 'balance' => 0.00]);

            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'customer_id' => $customer->id,
                'account_id' => $account->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'collected_by' => $userId,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            // Update target account balance
            $account->increment('balance', $amount);

            // 2. Allocate payment across unpaid or partial invoices (oldest first or specified)
            $remainingToAllocate = $amount;

            $invoicesQuery = Invoice::where('customer_id', $customer->id)
                ->whereIn('status', ['unpaid', 'partial'])
                ->lockForUpdate();

            if (!empty($data['invoice_ids'])) {
                $invoicesQuery->whereIn('id', $data['invoice_ids']);
            }

            $unpaidInvoices = $invoicesQuery->orderBy('due_date')->get();

            foreach ($unpaidInvoices as $inv) {
                if ($remainingToAllocate <= 0) break;

                $due = (float) $inv->due_amount;
                $allocation = min($remainingToAllocate, $due);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $inv->id,
                    'allocated_amount' => $allocation,
                ]);

                $newPaid = $inv->paid_amount + $allocation;
                $newDue = $inv->total - $newPaid;
                $newStatus = $newDue <= 0 ? 'paid' : 'partial';

                $inv->update([
                    'paid_amount' => $newPaid,
                    'due_amount' => max(0, $newDue),
                    'status' => $newStatus,
                ]);

                $remainingToAllocate -= $allocation;
            }

            // Deduct customer balance
            $customer->decrement('balance', $amount);

            AuditLog::log('payment_collected', 'billing', $payment, null, $payment->toArray());

            return $payment;
        });
    }

    /**
     * Reverse a payment safely (append-only principle).
     */
    public function reversePayment(Payment $payment, string $reason, int $userId): Payment
    {
        return DB::transaction(function () use ($payment, $reason, $userId) {
            if ($payment->status === 'reversed') {
                throw new Exception('Payment is already reversed.');
            }

            // Revert invoice allocations
            foreach ($payment->allocations as $alloc) {
                $inv = $alloc->invoice;
                if ($inv) {
                    $newPaid = max(0, $inv->paid_amount - $alloc->allocated_amount);
                    $newDue = $inv->total - $newPaid;
                    $inv->update([
                        'paid_amount' => $newPaid,
                        'due_amount' => $newDue,
                        'status' => $newPaid > 0 ? 'partial' : 'unpaid',
                    ]);
                }
            }

            // Revert account balance
            if ($payment->account) {
                $payment->account->decrement('balance', $payment->amount);
            }

            // Revert customer balance
            $payment->customer->increment('balance', $payment->amount);

            $payment->update([
                'status' => 'reversed',
                'notes' => $payment->notes . " | Reversed by User #{$userId}. Reason: {$reason}",
            ]);

            AuditLog::log('payment_reversed', 'billing', $payment, null, [
                'reason' => $reason,
                'reversed_by' => $userId,
            ]);

            return $payment;
        });
    }
}
