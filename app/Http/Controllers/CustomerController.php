<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PppoeCredential;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
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
            perPage: 15
        );

        $areas = Area::where('status', 'active')->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'area_id']),
            'areas' => $areas,
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
            'address' => 'required|string|max:500',
            'package_id' => 'nullable|exists:packages,id',
            'pppoe_username' => 'nullable|string|unique:pppoe_credentials,username|max:100',
            'pppoe_password' => 'nullable|string|min:4|max:100',
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
            'creator',
        ]);

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

        $customer->load(['area', 'primaryContact', 'installationAddress']);
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
        if ($connection->customer_id !== $customer->id) {
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
}
