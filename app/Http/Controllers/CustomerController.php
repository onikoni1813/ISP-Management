<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PppoeCredential;
use App\Services\CustomerService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
        protected SmsService $smsService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('customers.view');

        $customers = $this->customerService->searchCustomers(
            query: $request->input('search'),
            status: $request->input('status'),
            areaId: $request->input('area_id'),
            advancedFilter: $request->input('advanced_filter'),
            perPage: 15
        );

        $areas = Area::where('status', 'active')->get(['id', 'name', 'code']);
        $packages = \App\Models\Package::with('currentPrice')->where('status', 'active')->get(['id', 'name', 'code', 'speed_mbps']);
        $filterCounts = $this->customerService->getCustomerFilterCounts($request->input('area_id'));
        $smsTemplates = \App\Models\SmsTemplate::all(['id', 'name', 'template']);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'area_id', 'advanced_filter']),
            'areas' => $areas,
            'packages' => $packages,
            'filterCounts' => $filterCounts,
            'smsTemplates' => $smsTemplates,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('customers.create');

        $areas = Area::where('status', 'active')->get(['id', 'name', 'code']);
        $packages = Package::with('currentPrice')->where('status', 'active')->get();

        return Inertia::render('Admin/Customers/Create', [
            'areas' => $areas,
            'packages' => $packages,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('customers.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'area_id' => 'required|exists:areas,id',
            'address' => 'nullable|string|max:500',
            'package_id' => 'nullable|exists:packages,id',
            'pppoe_username' => 'required|string|unique:pppoe_credentials,username|max:100',
            'pppoe_password' => 'required|string|min:4|max:100',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:50',
            'router_model' => 'nullable|string|max:100',
            'billing_day' => 'nullable|integer|min:1|max:31',
            'notes' => 'nullable|string',
        ]);

        $customer = $this->customerService->createCustomer($validated, $request->user()->id);

        return redirect()->route('admin.customers.show', $customer->id)
            ->with('success', "Customer {$customer->customer_code} created successfully.");
    }

    /**
     * Display the specified customer central profile.
     */
    public function show(Customer $customer): Response
    {
        Gate::authorize('customers.view');

        $customer->load([
            'area',
            'contacts',
            'addresses',
            'connections.currentPackage.currentPrice',
            'connections.pppoeCredential',
            'packageHistories.package',
            'packageHistories.assigner',
            'customerNotes.author',
            'invoices.items',
            'payments.generatedInvoice',
            'renewals.package',
            'renewals.renewer',
            'creator',
        ]);

        $lastPaymentDate = $customer->payments->firstWhere('status', 'completed')?->paid_at ?? '1970-01-01';
        $latestZeroRenewal = $customer->renewals->firstWhere('is_zero_charge', true);
        $customer->has_active_zero_charge = $latestZeroRenewal && ($latestZeroRenewal->renewed_at >= $lastPaymentDate);
        if ($customer->has_active_zero_charge) {
            $customer->zero_charge_days = $latestZeroRenewal->validity_days;
            $customer->zero_charge_date = $latestZeroRenewal->renewed_at;
        }

        $packages = Package::with('currentPrice')->where('status', 'active')->get();

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer,
            'packages' => $packages,
            'canViewPppoePassword' => Gate::allows('pppoe.view_password'),
        ]);
    }

    /**
     * Show form for editing customer.
     */
    public function edit(Customer $customer): Response
    {
        Gate::authorize('customers.update');

        $customer->load(['area', 'primaryContact', 'installationAddress', 'connections.currentPackage']);
        $areas = Area::where('status', 'active')->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Customers/Edit', [
            'customer' => $customer,
            'areas' => $areas,
        ]);
    }

    /**
     * Update customer.
     */
    public function update(Request $request, Customer $customer)
    {
        Gate::authorize('customers.update');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'area_id' => 'required|exists:areas,id',
            'status' => 'required|in:active,expired,suspended,disconnected,pending,archived',
            'billing_day' => 'required|integer|min:1|max:31',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $this->customerService->updateCustomer($customer, $validated);

        return redirect()->route('admin.customers.show', $customer->id)
            ->with('success', 'Customer profile updated successfully.');
    }

    /**
     * Change package for connection.
     */
    public function changePackage(Request $request, Customer $customer, Connection $connection)
    {
        Gate::authorize('packages.update');

        // Prevent IDOR: Ensure connection belongs to target customer
        if ((int) $connection->customer_id !== (int) $customer->id) {
            abort(404, 'Connection does not belong to specified customer.');
        }

        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $this->customerService->assignPackage($customer, $connection, $validated['package_id'], $request->user()->id);

        return back()->with('success', 'Package updated successfully.');
    }

    /**
     * Securely reveal decrypted PPPoE password with audit logging.
     */
    public function revealPppoePassword(PppoeCredential $credential)
    {
        Gate::authorize('pppoe.view_password');

        AuditLog::log('pppoe_password_viewed', 'security', $credential, null, [
            'username' => $credential->username,
        ]);

        return response()->json([
            'username' => $credential->username,
            'password' => $credential->password, // Model auto-decrypts
        ]);
    }

    /**
     * Dispatch PPPoE ID, Password and Login portal link via SMS to customer.
     */
    public function sendCredentialsSms(Request $request, Customer $customer)
    {
        Gate::authorize('customers.edit');

        $phone = $customer->primaryContact?->phone ?? $customer->primaryContact?->phone_number;
        if (!$phone) {
            return back()->with('error', 'গ্রাহকের কোনো সক্রিয় ফোন নম্বর পাওয়া যায়নি।');
        }

        $log = $this->smsService->sendByTemplate(
            templateCode: 'pppoe_credentials',
            customer: $customer,
            extraVariables: [],
            userId: $request->user()->id,
            entityType: 'customer',
            entityId: $customer->id,
            force: true
        );

        if (!$log) {
            return back()->with('error', 'SMS পাঠানো সম্ভব হয়নি। অনুগ্রহ করে SMS গেটওয়ে ও টেমপ্লেট সক্রিয় আছে কিনা পরীক্ষা করুন।');
        }

        if ($log->status === 'sent') {
            return back()->with('success', "PPPoE ইউজারনেম, পাসওয়ার্ড ও লগইন লিংক সফলভাবে {$phone} নম্বরে SMS করা হয়েছে।");
        }

        return back()->with('error', "SMS পাঠাতে ত্রুটি হয়েছে: {$log->error_message}");
    }

    /**
     * Remove the specified customer from database.
     */
    public function destroy(Customer $customer)
    {
        Gate::authorize('customers.delete');

        $customerName = $customer->name;
        $customerCode = $customer->customer_code;

        $this->customerService->deleteCustomer($customer);

        return redirect()->route('admin.customers.index')
            ->with('success', "গ্রাহক {$customerName} ({$customerCode}) সফলভাবে মুছে ফেলা হয়েছে।");
    }
}

