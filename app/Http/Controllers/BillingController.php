<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Invoices listing.
     */
    public function invoices(Request $request): Response
    {
        Gate::authorize('billing.view');

        $invoices = Invoice::with(['customer', 'items'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q, $search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%"));
            })
            ->latest('id')
            ->paginate(15);

        return Inertia::render('Admin/Billing/Invoices', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Payments listing.
     */
    public function payments(Request $request): Response
    {
        Gate::authorize('payments.view');

        $payments = Payment::with(['customer', 'account', 'collector', 'allocations.invoice'])
            ->when($request->search, function ($q, $search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%"));
            })
            ->latest('id')
            ->paginate(15);

        return Inertia::render('Admin/Billing/Payments', [
            'payments' => $payments,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Collect payment for customer.
     */
    public function storePayment(Request $request, Customer $customer)
    {
        Gate::authorize('billing.collect');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,bkash,nagad,bank,other',
            'account_id' => 'nullable|exists:accounts,id',
            'reference' => 'nullable|string|max:100',
            'idempotency_key' => 'nullable|string|max:100',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'exists:invoices,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $payment = $this->billingService->collectPayment($customer, $validated, $request->user()->id);

        return back()->with('success', "Payment {$payment->payment_number} of ৳{$payment->amount} recorded successfully.");
    }

    /**
     * Printable Payment Money Receipt.
     */
    public function receipt(Payment $payment): Response
    {
        Gate::authorize('payments.view');

        $payment->load(['customer.primaryContact', 'customer.installationAddress', 'account', 'collector', 'allocations.invoice']);

        return Inertia::render('Admin/Billing/Receipt', [
            'payment' => $payment,
        ]);
    }

    /**
     * Reverse payment safely.
     */
    public function reverse(Request $request, Payment $payment)
    {
        Gate::authorize('payments.reverse');

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $this->billingService->reversePayment($payment, $validated['reason'], $request->user()->id);

        return back()->with('success', "Payment {$payment->payment_number} reversed successfully.");
    }
}
