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

        $query = Invoice::with(['customer', 'items'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%"));
                });
            })
            ->when($request->date_from, fn($q) => $q->whereDate('due_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('due_date', '<=', $request->date_to));

        $invoices = (clone $query)->latest('id')->paginate(15)->withQueryString();

        // High-level ledger metrics
        $metrics = [
            'total_invoices' => Invoice::count(),
            'paid_invoices' => Invoice::where('status', 'paid')->count(),
            'partial_invoices' => Invoice::where('status', 'partial')->count(),
            'unpaid_invoices' => Invoice::where('status', 'unpaid')->count(),
            'total_billed' => (float) Invoice::sum('total'),
            'total_collected' => (float) Invoice::sum('paid_amount'),
            'total_due' => (float) Invoice::sum('due_amount'),
        ];

        return Inertia::render('Admin/Billing/Invoices', [
            'invoices' => $invoices,
            'metrics' => $metrics,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Payments listing.
     */
    public function payments(Request $request): Response
    {
        Gate::authorize('payments.view');

        $payments = Payment::with(['customer', 'account', 'collector', 'approver', 'generatedInvoice', 'allocations.invoice'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q, $search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%"));
            })
            ->latest('id')
            ->paginate(15);

        return Inertia::render('Admin/Billing/Payments', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'status']),
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
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,bkash,nagad,bank,other',
            'account_id' => 'nullable|exists:accounts,id',
            'reference' => 'nullable|string|max:100',
            'idempotency_key' => 'nullable|string|max:100',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'exists:invoices,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $isStaff = $user->hasRole('staff') && !$user->hasRole('admin');

        // If collected by staff, strictly enforce package rate: staff cannot change base price, only discount
        if ($isStaff) {
            $connection = $customer->connections()->with('currentPackage.currentPrice')->first();
            $basePrice = (float) ($connection?->currentPackage?->currentPrice?->price ?? 0);
            $discount = (float) ($validated['discount'] ?? 0);

            if ($discount > $basePrice) {
                return back()->withErrors(['discount' => "ডিস্কাউন্ট (৳{$discount}) মূল প্যাকেজ বিল (৳{$basePrice})-এর চেয়ে বেশি হতে পারে না।"]);
            }

            // Enforce exact calculated amount
            $validated['amount'] = max(1, $basePrice - $discount);
        }

        $payment = $this->billingService->collectPayment($customer, $validated, $user->id);

        // If collected by staff, redirect to staff dashboard with success message
        if ($isStaff) {
            return redirect()->route('staff.dashboard')->with('success', "৳{$payment->amount} বিল আদায় সফল হয়েছে! অ্যাডমিন অনুমোদনের পর ইনভয়েস তৈরি হবে।");
        }

        return back()->with('success', "Payment {$payment->payment_number} of ৳{$payment->amount} recorded successfully.");
    }

    /**
     * Admin approve pending payment.
     */
    public function approve(Request $request, Payment $payment)
    {
        Gate::authorize('billing.collect');

        $this->billingService->approvePayment($payment, $request->user()->id);

        return back()->with('success', "পেমেন্ট {$payment->payment_number} সফলভাবে অনুমোদিত হয়েছে এবং ইনভয়েস জেনারেট হয়েছে।");
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
     * Printable / Viewable Customer Invoice.
     */
    public function showInvoice(Invoice $invoice): Response
    {
        Gate::authorize('invoices.view');

        $invoice->load([
            'customer.primaryContact',
            'customer.installationAddress',
            'customer.connections.pppoeCredential',
            'items',
            'allocations.payment',
        ]);

        return Inertia::render('Admin/Billing/Invoice', [
            'invoice' => $invoice,
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
