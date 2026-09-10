<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Customer;
use App\Models\Renewal;
use App\Services\RenewalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RenewalController extends Controller
{
    public function __construct(
        protected RenewalService $renewalService
    ) {}

    /**
     * Renewal History Listing.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('renewals.view');

        $renewals = Renewal::with(['customer', 'connection', 'package', 'renewer'])
            ->when($request->search, function ($q, $search) {
                $q->where('renewal_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%"));
            })
            ->latest('id')
            ->paginate(15);

        return Inertia::render('Admin/Billing/Renewals', [
            'renewals' => $renewals,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Process Renewal for customer connection.
     */
    public function store(Request $request, Customer $customer, Connection $connection)
    {
        Gate::authorize('renewals.create');

        $validated = $request->validate([
            'package_id' => 'nullable|exists:packages,id',
            'validity_days' => 'required|integer|min:1|max:365',
            'is_zero_charge' => 'nullable|boolean',
            'mode' => 'nullable|string|in:standard,deduct_shift',
            'amount' => 'nullable|numeric|min:0',
            'collect_payment' => 'nullable|boolean',
            'payment_method' => 'nullable|string|in:cash,bkash,nagad,bank,other',
            'idempotency_key' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $renewal = $this->renewalService->processRenewal($customer, $connection, $validated, $request->user()->id);

        return back()->with('success', "Connection renewed successfully. New Expiry: {$renewal->new_expiry}");
    }
}
