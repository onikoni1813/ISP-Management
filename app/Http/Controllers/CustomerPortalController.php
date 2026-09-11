<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Renewal;
use App\Services\BillingService;
use App\Services\ComplaintService;
use App\Services\RenewalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPortalController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected RenewalService $renewalService,
        protected ComplaintService $complaintService,
    ) {}

    /**
     * Resolve authenticated user's linked customer record.
     */
    protected function getCustomer(Request $request): Customer
    {
        $user = $request->user();

        // 1. Check direct user_id on customer table
        $customer = Customer::where('user_id', $user->id)->first();

        // 2. If not found, match by primary contact phone or email
        if (!$customer) {
            $customer = Customer::whereHas('contacts', function ($query) use ($user) {
                $query->where('phone', $user->phone)
                    ->orWhere('email', $user->email);
            })->first();

            // Link customer if matched
            if ($customer && !$customer->user_id) {
                $customer->update(['user_id' => $user->id]);
            }
        }

        if (!$customer) {
            abort(404, 'No subscriber account found linked to your profile. Please contact Pirgacha Internet support.');
        }

        return $customer;
    }

    /**
     * Customer Portal Central Dashboard (/account).
     */
    public function dashboard(Request $request): Response
    {
        $customer = $this->getCustomer($request);

        $customer->load([
            'area',
            'primaryContact',
            'installationAddress',
            'connections.currentPackage.currentPrice',
            'connections.pppoeCredential',
        ]);

        $primaryConnection = $customer->connections->first();

        // Active package & billing metrics
        $invoices = Invoice::where('customer_id', $customer->id)
            ->latest('id')
            ->take(5)
            ->get();

        $payments = Payment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->latest('paid_at')
            ->take(5)
            ->get();

        $complaints = Complaint::where('customer_id', $customer->id)
            ->latest('id')
            ->take(5)
            ->get();

        return Inertia::render('Account/Dashboard', [
            'customer' => $customer,
            'primaryConnection' => $primaryConnection,
            'invoices' => $invoices,
            'payments' => $payments,
            'complaints' => $complaints,
        ]);
    }

    /**
     * Invoices list (/account/invoices).
     */
    public function invoices(Request $request): Response
    {
        $customer = $this->getCustomer($request);

        $invoices = Invoice::with('items')
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->paginate(10);

        return Inertia::render('Account/Invoices', [
            'customer' => $customer,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Payments & receipts list (/account/payments).
     */
    public function payments(Request $request): Response
    {
        $customer = $this->getCustomer($request);

        $payments = Payment::with('allocations.invoice')
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->paginate(10);

        return Inertia::render('Account/Payments', [
            'customer' => $customer,
            'payments' => $payments,
        ]);
    }

    /**
     * View customer's money receipt with IDOR check.
     */
    public function receipt(Request $request, Payment $payment): Response
    {
        $customer = $this->getCustomer($request);

        // Prevent IDOR: Customer can only view their own receipts
        if ($payment->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to money receipt.');
        }

        $payment->load(['customer.primaryContact', 'customer.installationAddress', 'account', 'collector', 'allocations.invoice']);

        return Inertia::render('Admin/Billing/Receipt', [
            'payment' => $payment,
        ]);
    }

    /**
     * Complaints listing and ticket submission (/account/complaints).
     */
    public function complaints(Request $request): Response
    {
        $customer = $this->getCustomer($request);

        $complaints = Complaint::with(['comments.user'])
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->paginate(10);

        return Inertia::render('Account/Complaints', [
            'customer' => $customer,
            'complaints' => $complaints,
        ]);
    }

    /**
     * Submit support ticket from portal.
     */
    public function storeComplaint(Request $request)
    {
        $customer = $this->getCustomer($request);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|string|in:low,normal,high,urgent',
        ]);

        $complaint = $this->complaintService->createComplaint($customer, $validated, $request->user()->id);

        return back()->with('success', "Support ticket #{$complaint->complaint_number} submitted. Our NOC team has been notified.");
    }

    /**
     * Add reply to complaint from customer.
     */
    public function commentComplaint(Request $request, Complaint $complaint)
    {
        $customer = $this->getCustomer($request);

        if ($complaint->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to complaint.');
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $this->complaintService->addComment($complaint, $validated['comment'], $request->user()->id, false);

        return back()->with('success', 'Comment added.');
    }

    /**
     * Self-service Renewal Screen (/account/renewal).
     */
    public function renewal(Request $request): Response
    {
        $customer = $this->getCustomer($request);

        $customer->load(['connections.currentPackage.currentPrice']);
        $primaryConnection = $customer->connections->first();

        return Inertia::render('Account/Renewal', [
            'customer' => $customer,
            'primaryConnection' => $primaryConnection,
        ]);
    }

    /**
     * Submit Renewal from Customer Portal.
     */
    public function storeRenewal(Request $request)
    {
        $customer = $this->getCustomer($request);
        $primaryConnection = $customer->connections->first();

        if (!$primaryConnection) {
            return back()->withErrors(['error' => 'No active connection found to renew.']);
        }

        $validated = $request->validate([
            'validity_days' => 'required|integer|in:30,60,90',
            'payment_method' => 'required|string|in:bkash,nagad,cash',
            'reference' => 'nullable|string|max:100',
        ]);

        $pkgPrice = $primaryConnection->currentPackage?->currentPrice?->price ?? 500;
        $multiplier = (int) ($validated['validity_days'] / 30);
        $totalAmount = $pkgPrice * max(1, $multiplier);

        $renewal = $this->renewalService->processRenewal($customer, $primaryConnection, [
            'validity_days' => $validated['validity_days'],
            'amount' => $totalAmount,
            'collect_payment' => true,
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'] ?? 'Online Self-Renewal',
            'notes' => 'Customer self-service portal renewal',
        ], $request->user()->id);

        return redirect()->route('account.dashboard')->with('success', "Renewal successful! Your internet validity has been extended to {$renewal->new_expiry}.");
    }

    /**
     * Customer Profile (/account/profile).
     */
    public function profile(Request $request): Response
    {
        $customer = $this->getCustomer($request);
        $customer->load(['area', 'primaryContact', 'installationAddress', 'connections.currentPackage']);

        return Inertia::render('Account/Profile', [
            'customer' => $customer,
            'user' => $request->user(),
        ]);
    }
}
