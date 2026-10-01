<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Renewal;
use Carbon\Carbon;
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

            $discount = (float) ($data['discount'] ?? 0.00);

            // Check if user is staff (non-admin) collecting in the field
            $user = \App\Models\User::find($userId);
            $isFieldStaff = $user && $user->hasRole('staff') && !$user->hasRole('admin');
            $status = $isFieldStaff ? 'pending' : 'completed';

            // Target Account (if pending, account balance increments upon admin approval)
            $account = !empty($data['account_id']) 
                ? Account::lockForUpdate()->findOrFail($data['account_id'])
                : Account::firstOrCreate(['name' => 'Cash in Hand'], ['type' => 'cash', 'balance' => 0.00]);

            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'customer_id' => $customer->id,
                'account_id' => $account->id,
                'amount' => $amount,
                'discount' => $discount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'collected_by' => $userId,
                'status' => $status,
                'approved_by' => !$isFieldStaff ? $userId : null,
                'approved_at' => !$isFieldStaff ? now() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            // If field staff, keep as pending approval - Do not alter accounts or generate paid invoice yet
            if ($isFieldStaff) {
                AuditLog::log('payment_collected_pending_approval', 'billing', $payment, null, $payment->toArray());
                return $payment;
            }

            // For admin or direct collections: complete immediately
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

            // Automated Payment Confirmation SMS (Master Plan Milestone 12)
            \App\Jobs\SendCustomerSmsJob::dispatch(
                'payment_received',
                $customer->id,
                [
                    'amount' => number_format($amount, 2),
                    'due' => number_format((float) max(0, -$customer->fresh()->balance), 2),
                ],
                $userId,
                \App\Models\Payment::class,
                $payment->id
            );

            return $payment;
        });
    }

    /**
     * Approve a pending payment, generate official paid invoice, and reconcile accounts.
     */
    public function approvePayment(Payment $payment, int $adminId): Payment
    {
        return DB::transaction(function () use ($payment, $adminId) {
            if ($payment->status !== 'pending') {
                throw new Exception("Only pending payments can be approved. Current status: {$payment->status}");
            }

            $customer = $payment->customer()->lockForUpdate()->firstOrFail();
            $amount = (float) $payment->amount;
            $discount = (float) ($payment->discount ?? 0.00);

            // 1. Reconcile receiving account balance
            $account = $payment->account ? Account::lockForUpdate()->find($payment->account_id) : null;
            if ($account) {
                $account->increment('balance', $amount);
            }

            // 2. Determine PPPoE connection and active package
            $connection = $customer->connections()
                ->with(['currentPackage.currentPrice', 'pppoeCredential'])
                ->first();

            $package = $connection?->currentPackage;
            $pppoeUsername = $connection?->pppoeCredential?->username ?? 'N/A';
            $packageName = $package?->name ?? 'Internet Package';
            $speed = $package?->speed_mbps ?? 0;

            // 3. Generate Official Paid Invoice associated with Customer & PPPoE Service
            $lastInvoice = Invoice::lockForUpdate()->latest('id')->first();
            $nextNumber = $lastInvoice ? ($lastInvoice->id + 1) : 1;
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

            $periodStart = now()->startOfMonth()->toDateString();
            $periodEnd = now()->endOfMonth()->toDateString();

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'billing_cycle_id' => null,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'due_date' => now()->toDateString(),
                'subtotal' => $amount + $discount,
                'discount' => $discount,
                'tax' => 0.00,
                'total' => $amount,
                'paid_amount' => $amount,
                'due_amount' => 0.00,
                'status' => 'paid',
                'created_by' => $adminId,
                'notes' => "Auto-generated upon payment approval. PPPoE: {$pppoeUsername}, Package: {$packageName} ({$speed} Mbps)",
            ]);

            // Create Invoice Item
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => 'package',
                'description' => "Internet Service: {$packageName} ({$speed} Mbps) - PPPoE: {$pppoeUsername}",
                'unit_price' => $amount + $discount,
                'quantity' => 1,
                'total' => $amount + $discount,
            ]);

            // Allocate Payment to this Generated Invoice
            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'allocated_amount' => $amount,
            ]);

            // Update Payment to completed with approval audit info & invoice link
            $payment->update([
                'status' => 'completed',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'generated_invoice_id' => $invoice->id,
            ]);

            // Deduct customer balance if customer has outstanding debt
            if ((float) $customer->balance > 0) {
                $customer->decrement('balance', min((float) $customer->balance, $amount));
            }

            // 4. Update Connection Expiry & Record Renewal
            if ($connection) {
                $validityDays = (int) ($package?->currentPrice?->validity_days ?? 30);
                $prevExpiry = $connection->expiry_date ? Carbon::parse($connection->expiry_date) : null;
                $today = Carbon::today();
                $baseDate = ($prevExpiry && $prevExpiry->greaterThanOrEqualTo($today)) ? $prevExpiry : $today;
                $newExpiry = $baseDate->copy()->addDays($validityDays)->toDateString();

                $connection->update([
                    'expiry_date' => $newExpiry,
                    'status' => 'active',
                ]);

                // Create Renewal Record for metrics and history tracking
                $lastRenewal = Renewal::lockForUpdate()->latest('id')->first();
                $nextRenNumber = $lastRenewal ? ($lastRenewal->id + 1) : 1;
                $renewalNumber = 'REN-' . date('Y') . '-' . str_pad((string) $nextRenNumber, 6, '0', STR_PAD_LEFT);

                Renewal::create([
                    'renewal_number' => $renewalNumber,
                    'customer_id' => $customer->id,
                    'connection_id' => $connection->id,
                    'package_id' => $package?->id,
                    'invoice_id' => $invoice->id,
                    'renewed_by' => $payment->collected_by ?? $adminId,
                    'previous_expiry' => $prevExpiry?->toDateString(),
                    'new_expiry' => $newExpiry,
                    'validity_days' => $validityDays,
                    'amount' => $amount,
                    'mode' => 'standard',
                    'is_zero_charge' => false,
                    'renewed_at' => now(),
                ]);
            }

            // Ensure customer status is active
            $customer->update(['status' => 'active']);

            AuditLog::log('payment_approved', 'billing', $payment, null, [
                'admin_id' => $adminId,
                'generated_invoice_id' => $invoice->id,
                'amount' => $amount,
                'discount' => $discount,
            ]);

            // Automated Payment Confirmation SMS to Customer
            \App\Jobs\SendCustomerSmsJob::dispatch(
                'payment_received',
                $customer->id,
                [
                    'amount' => number_format($amount, 2),
                    'due' => number_format((float) max(0, $customer->fresh()->balance), 2),
                ],
                $adminId,
                \App\Models\Payment::class,
                $payment->id
            );

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
