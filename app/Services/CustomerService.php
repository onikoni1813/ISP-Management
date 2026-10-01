<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerPackage;
use App\Models\Package;
use App\Models\PppoeCredential;
use App\Models\Renewal;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Search and paginate customers with eager loaded relationships and advanced filters.
     */
    public function searchCustomers(?string $query = null, ?string $status = null, ?int $areaId = null, ?string $advancedFilter = null, int $perPage = 15): LengthAwarePaginator
    {
        $today = now()->toDateString();
        $in3Days = now()->addDays(3)->toDateString();
        $in7Days = now()->addDays(7)->toDateString();

        return Customer::query()
            ->with(['area', 'primaryContact', 'connections.currentPackage', 'connections.pppoeCredential'])
            ->when($status, fn(Builder $q) => $q->where('status', $status))
            ->when($areaId, fn(Builder $q) => $q->where('area_id', $areaId))
            ->when($advancedFilter, function (Builder $q) use ($advancedFilter, $today, $in3Days, $in7Days) {
                if ($advancedFilter === 'expiring_3d') {
                    // Next 3 days expiry
                    $q->whereHas('connections', fn($c) => $c->whereBetween('expiry_date', [$today, $in3Days]));
                } elseif ($advancedFilter === 'expiring_7d') {
                    // Next 7 days expiry
                    $q->whereHas('connections', fn($c) => $c->whereBetween('expiry_date', [$today, $in7Days]));
                } elseif ($advancedFilter === 'expired') {
                    // Already expired
                    $q->whereHas('connections', fn($c) => $c->whereNotNull('expiry_date')->where('expiry_date', '<', $today));
                } elseif ($advancedFilter === 'due') {
                    // Has outstanding debt/balance or unpaid invoices
                    $q->where(function ($sub) {
                        $sub->where('balance', '>', 0)
                            ->orWhereHas('invoices', fn($inv) => $inv->where('due_amount', '>', 0));
                    });
                } elseif ($advancedFilter === 'zero_charge_renewed') {
                    // Customers who took advance grace validity (is_zero_charge = 1) without full paid renewal since
                    $q->whereHas('renewals', function ($r) {
                        $r->where('is_zero_charge', true)
                          ->whereRaw('renewals.renewed_at >= COALESCE((SELECT MAX(p.paid_at) FROM payments p WHERE p.customer_id = renewals.customer_id), "1970-01-01")');
                    });
                } elseif ($advancedFilter === 'paid_this_month') {
                    // Customers who completed payment this month and have no pending due
                    $startOfMonth = now()->startOfMonth()->toDateTimeString();
                    $endOfMonth = now()->endOfMonth()->toDateTimeString();
                    $q->whereHas('payments', function ($p) use ($startOfMonth, $endOfMonth) {
                        $p->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                          ->where('status', 'completed');
                    })->where(function ($sub) {
                        $sub->where('balance', '<=', 0)
                            ->whereDoesntHave('invoices', fn($inv) => $inv->where('due_amount', '>', 0));
                    });
                }
            })
            ->when($query, function (Builder $q) use ($query) {
                $q->where(function (Builder $sub) use ($query) {
                    $sub->where('customer_code', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%")
                        ->orWhereHas('contacts', fn($c) => $c->where('phone', 'like', "%{$query}%"))
                        ->orWhereHas('connections.pppoeCredential', fn($p) => $p->where('username', 'like', "%{$query}%"))
                        ->orWhereHas('connections', fn($c) => $c->where('ip_address', 'like', "%{$query}%")->orWhere('mac_address', 'like', "%{$query}%"));
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get real-time counts for advanced customer categories.
     */
    public function getCustomerFilterCounts(?int $areaId = null): array
    {
        $today = now()->toDateString();
        $in3Days = now()->addDays(3)->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateTimeString();
        $endOfMonth = now()->endOfMonth()->toDateTimeString();

        $base = Customer::query()->when($areaId, fn($q) => $q->where('area_id', $areaId));

        return [
            'all' => (clone $base)->count(),
            'due' => (clone $base)->where(function ($sub) {
                $sub->where('balance', '>', 0)
                    ->orWhereHas('invoices', fn($inv) => $inv->where('due_amount', '>', 0));
            })->count(),
            'expiring_3d' => (clone $base)->whereHas('connections', fn($c) => $c->whereBetween('expiry_date', [$today, $in3Days]))->count(),
            'expired' => (clone $base)->whereHas('connections', fn($c) => $c->whereNotNull('expiry_date')->where('expiry_date', '<', $today))->count(),
            'zero_charge_renewed' => (clone $base)->whereHas('renewals', function ($r) {
                $r->where('is_zero_charge', true)
                  ->whereRaw('renewals.renewed_at >= COALESCE((SELECT MAX(p.paid_at) FROM payments p WHERE p.customer_id = renewals.customer_id), "1970-01-01")');
            })->count(),
            'paid_this_month' => (clone $base)->whereHas('payments', function ($p) use ($startOfMonth, $endOfMonth) {
                $p->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                  ->where('status', 'completed');
            })->where(function ($sub) {
                $sub->where('balance', '<=', 0)
                    ->whereDoesntHave('invoices', fn($inv) => $inv->where('due_amount', '>', 0));
            })->count(),
        ];
    }

    /**
     * Calculate next expiry date aligned with the customer's monthly Billing Day.
     * E.g. Joined 21 Sep with Billing Day 8 -> Expiry 08 Oct (upcoming 8th).
     */
    public function calculateExpiryFromBillingDay(?int $billingDay, ?\Carbon\Carbon $fromDate = null): string
    {
        $today = $fromDate ? $fromDate->copy() : now();
        $billingDay = (int) ($billingDay ?: 1);
        $billingDay = max(1, min(31, $billingDay));

        // Attempt target date in the current calendar month
        $targetThisMonth = $today->copy();
        $dayThisMonth = min($billingDay, $targetThisMonth->daysInMonth);
        $targetThisMonth->day($dayThisMonth);

        // If target day in current month is strictly after today, expiry is in current month.
        // Otherwise (it is today or earlier in the month), expiry falls into the upcoming next month.
        if ($targetThisMonth->greaterThan($today->copy()->startOfDay())) {
            return $targetThisMonth->toDateString();
        }

        $targetNextMonth = $today->copy()->addMonthNoOverflow();
        $dayNextMonth = min($billingDay, $targetNextMonth->daysInMonth);
        $targetNextMonth->day($dayNextMonth);

        return $targetNextMonth->toDateString();
    }

    /**
     * Create a customer with contact, address, connection, and PPPoE credentials atomically.
     */
    public function createCustomer(array $data, ?int $userId = null): Customer
    {
        return DB::transaction(function () use ($data, $userId) {
            $lastCustomer = Customer::lockForUpdate()->latest('id')->first();
            $nextNumber = $lastCustomer ? ($lastCustomer->id + 1) : 1;
            $customerCode = 'CUST-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

            $billingDay = (int) ($data['billing_day'] ?? 1);

            $customer = Customer::create([
                'customer_code' => $customerCode,
                'name' => $data['name'],
                'area_id' => $data['area_id'],
                'status' => $data['status'] ?? 'active',
                'join_date' => $data['join_date'] ?? now()->toDateString(),
                'balance' => 0.00,
                'billing_day' => $billingDay,
                'created_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create Primary Contact
            CustomerContact::create([
                'customer_id' => $customer->id,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'is_primary' => true,
            ]);

            // Create Installation Address if provided
            if (!empty($data['address'])) {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'address_type' => 'installation',
                    'full_address' => $data['address'],
                    'is_default' => true,
                ]);
            }

            // Create initial Connection if package provided
            if (!empty($data['package_id'])) {
                $package = Package::with('currentPrice')->findOrFail($data['package_id']);
                $connCode = 'CON-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

                // Expiry calculation aligned to customer's monthly Billing Day
                $expiryDate = $this->calculateExpiryFromBillingDay($billingDay);

                $connection = Connection::create([
                    'connection_code' => $connCode,
                    'customer_id' => $customer->id,
                    'area_id' => $data['area_id'] ?? null,
                    'current_package_id' => $package->id,
                    'protocol' => $data['protocol'] ?? 'pppoe',
                    'ip_address' => $data['ip_address'] ?? null,
                    'mac_address' => $data['mac_address'] ?? null,
                    'router_model' => $data['router_model'] ?? null,
                    'status' => 'active',
                    'installation_date' => now()->toDateString(),
                    'expiry_date' => $expiryDate,
                ]);

                // Encrypted PPPoE Credentials
                if (!empty($data['pppoe_username']) && !empty($data['pppoe_password'])) {
                    PppoeCredential::create([
                        'connection_id' => $connection->id,
                        'username' => $data['pppoe_username'],
                        'password' => $data['pppoe_password'], // Encrypted via model cast
                        'status' => 'active',
                    ]);
                }

                // Initial Customer Package History
                $appliedPrice = $package->currentPrice ? $package->currentPrice->price : 0.00;
                CustomerPackage::create([
                    'customer_id' => $customer->id,
                    'connection_id' => $connection->id,
                    'package_id' => $package->id,
                    'actual_price' => $appliedPrice,
                    'start_date' => now()->toDateString(),
                    'assigned_by' => $userId,
                    'status' => 'active',
                    'remarks' => 'Initial subscription',
                ]);
            }

            AuditLog::log('customer_created', 'customer', $customer, null, $customer->toArray());

            return $customer;
        });
    }

    /**
     * Update customer information.
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $oldValues = $customer->toArray();

            $newBillingDay = isset($data['billing_day']) ? (int) $data['billing_day'] : null;
            $billingDayChanged = $newBillingDay && ($newBillingDay !== (int) $oldValues['billing_day']);

            $customer->update([
                'name' => $data['name'] ?? $customer->name,
                'area_id' => $data['area_id'] ?? $customer->area_id,
                'status' => $data['status'] ?? $customer->status,
                'billing_day' => $newBillingDay ?? $customer->billing_day,
                'notes' => $data['notes'] ?? $customer->notes,
                'updated_by' => auth()->id(),
            ]);

            // If explicit expiry_date is provided by admin, update connection expiry directly
            if (!empty($data['expiry_date'])) {
                foreach ($customer->connections as $conn) {
                    $conn->update(['expiry_date' => $data['expiry_date']]);
                }
            } elseif ($billingDayChanged) {
                // Otherwise if billing day changed, align active connection's expiry date
                foreach ($customer->connections as $conn) {
                    if ($conn->expiry_date) {
                        $currentExpiry = \Carbon\Carbon::parse($conn->expiry_date);
                        $targetDay = min($newBillingDay, $currentExpiry->daysInMonth);
                        $newConnExpiry = $currentExpiry->copy()->day($targetDay);
                        $conn->update(['expiry_date' => $newConnExpiry->toDateString()]);
                    }
                }
            }

            if (!empty($data['phone'])) {
                $contact = $customer->primaryContact;
                if ($contact) {
                    $contact->update(['phone' => $data['phone']]);
                }
            }

            AuditLog::log('customer_updated', 'customer', $customer, $oldValues, $customer->toArray());

            return $customer;
        });
    }

    /**
     * Assign / Change package for a customer connection preserving historical pricing.
     */
    public function assignPackage(Customer $customer, Connection $connection, int $packageId, int $userId): CustomerPackage
    {
        return DB::transaction(function () use ($customer, $connection, $packageId, $userId) {
            $newPackage = Package::with('currentPrice')->findOrFail($packageId);
            $appliedPrice = $newPackage->currentPrice ? $newPackage->currentPrice->price : 0.00;

            // Determine whether this change is an upgrade or downgrade
            $currentPkg = $connection->currentPackage;
            $currentPrice = $currentPkg && $currentPkg->currentPrice ? (float) $currentPkg->currentPrice->price : null;
            $previousStatus = 'upgraded';
            if ($currentPrice !== null) {
                if ((float) $appliedPrice < $currentPrice) {
                    $previousStatus = 'downgraded';
                } elseif ((float) $appliedPrice > $currentPrice) {
                    $previousStatus = 'upgraded';
                } else {
                    $previousStatus = 'changed';
                }
            }

            // Mark previous active package history as upgraded/downgraded/superseded
            CustomerPackage::where('customer_id', $customer->id)
                ->where('connection_id', $connection->id)
                ->where('status', 'active')
                ->update([
                    'status' => $previousStatus,
                    'end_date' => now()->toDateString(),
                ]);

            // Update connection's current package pointer
            $connection->update(['current_package_id' => $newPackage->id]);

            // Create new immutable package history record
            $history = CustomerPackage::create([
                'customer_id' => $customer->id,
                'connection_id' => $connection->id,
                'package_id' => $newPackage->id,
                'actual_price' => $appliedPrice,
                'start_date' => now()->toDateString(),
                'assigned_by' => $userId,
                'status' => 'active',
                'remarks' => 'Package changed to ' . $newPackage->name,
            ]);

            AuditLog::log('package_changed', 'customer', $customer, null, [
                'package_id' => $newPackage->id,
                'price' => $appliedPrice,
                'action_type' => $previousStatus,
            ]);

            return $history;
        });
    }

    /**
     * Delete/Archive a customer with associated portal account cleanup and audit logging.
     */
    public function deleteCustomer(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            $customerData = $customer->toArray();
            $portalUser = $customer->user;

            AuditLog::log('customer_deleted', 'customer', $customer, $customerData, null);

            // Delete customer (cascades contacts, addresses, connections, pppoe_credentials, packages, etc.)
            $customer->delete();

            // If a portal user account was created specifically for this customer, remove it as well
            if ($portalUser && $portalUser->hasRole('customer')) {
                $portalUser->delete();
            }
        });
    }

    /**
     * Move a customer to a specific state or billing category with automatic ledger and expiry alignment.
     */
    public function moveCustomerCategory(Customer $customer, string $targetCategory, array $options, int $userId): array
    {
        return DB::transaction(function () use ($customer, $targetCategory, $options, $userId) {
            $connections = $customer->connections;
            $connection = $connections->first();
            $package = $connection ? $connection->currentPackage()->with('currentPrice')->first() : null;
            $today = Carbon::today();
            $startOfMonth = now()->startOfMonth()->toDateTimeString();
            $endOfMonth = now()->endOfMonth()->toDateTimeString();

            switch ($targetCategory) {
                case 'zero_charge_renewed':
                    $days = (int) ($options['validity_days'] ?? 3);
                    if ($days < 1) {
                        $days = 3;
                    }

                    // 1. Reverse accidental paid renewals / payments made this month if requested
                    if (!empty($options['reverse_accidental_payment'])) {
                        $completedPayments = $customer->payments()
                            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                            ->where('status', 'completed')
                            ->get();

                        foreach ($completedPayments as $payment) {
                            app(BillingService::class)->reversePayment(
                                $payment,
                                $options['notes'] ?? 'গ্রাহক মুভ: ভুলবশত পেইড রিনিউ রিভার্স করে গ্রেস প্রদান',
                                $userId
                            );
                        }

                        // 2. Void/cancel any unwanted unpaid invoices created during that accidental renewal
                        if (!empty($options['void_renewal_invoice'])) {
                            $unpaidInvoices = $customer->invoices()
                                ->where('status', 'unpaid')
                                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                                ->get();

                            foreach ($unpaidInvoices as $inv) {
                                if ($inv->due_amount > 0) {
                                    $customer->decrement('balance', $inv->due_amount);
                                }
                                $inv->update(['status' => 'cancelled', 'due_amount' => 0]);
                            }
                        }

                        // Ensure balance is clean
                        if ($customer->fresh()->balance < 0) {
                            $customer->update(['balance' => 0]);
                        }
                    }

                    // 3. Set connection expiry to today + days
                    $newExpiry = $today->copy()->addDays($days)->toDateString();
                    if ($connections->isNotEmpty()) {
                        foreach ($connections as $conn) {
                            $conn->update([
                                'expiry_date' => $newExpiry,
                                'status' => 'active',
                            ]);
                        }
                    }
                    $customer->update(['status' => 'active']);

                    // 4. Create zero-charge renewal record
                    Renewal::create([
                        'renewal_number' => 'REN-MV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                        'customer_id' => $customer->id,
                        'connection_id' => $connection?->id,
                        'package_id' => $package?->id,
                        'previous_expiry' => $connection?->getOriginal('expiry_date'),
                        'new_expiry' => $newExpiry,
                        'validity_days' => $days,
                        'amount' => 0.00,
                        'is_zero_charge' => true,
                        'renewal_type' => 'grace_move',
                        'renewed_by' => $userId,
                        'renewed_at' => now(),
                        'notes' => $options['notes'] ?? "ক্যাটাগরি মুভ: {$days} দিনের গ্রেস প্রদান",
                    ]);
                    break;

                case 'paid_this_month':
                    $validityDays = $package?->currentPrice?->validity_days ?? 30;
                    $amount = (float) ($options['amount'] ?? ($package?->currentPrice?->price ?? 0));
                    $paymentMethod = $options['payment_method'] ?? 'cash';

                    // Determine new expiry date
                    $currentExpiry = $connection?->expiry_date ? Carbon::parse($connection->expiry_date) : null;
                    $baseDate = ($currentExpiry && $currentExpiry->greaterThanOrEqualTo($today)) ? $currentExpiry : $today;
                    $newExpiry = $baseDate->copy()->addDays($validityDays)->toDateString();

                    if ($connections->isNotEmpty()) {
                        foreach ($connections as $conn) {
                            $conn->update([
                                'expiry_date' => $newExpiry,
                                'status' => 'active',
                            ]);
                        }
                    }
                    $customer->update(['status' => 'active']);

                    // Check if customer already has a completed payment this month
                    $hasCompletedPayment = $customer->payments()
                        ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                        ->where('status', 'completed')
                        ->exists();

                    if (!$hasCompletedPayment) {
                        // Check if an unpaid invoice already exists to pay off
                        $existingUnpaid = $customer->invoices()
                            ->where('due_amount', '>', 0)
                            ->first();

                        if ($existingUnpaid) {
                            app(BillingService::class)->collectPayment($customer, [
                                'amount' => $existingUnpaid->due_amount,
                                'payment_method' => $paymentMethod,
                                'paid_at' => now()->addSecond(),
                                'invoice_ids' => [$existingUnpaid->id],
                                'notes' => 'মুভ টু চলতি বিল পরিশোধিত - বকেয়া পরিশোধ',
                            ], $userId);
                        } else {
                            $invoice = app(BillingService::class)->createInvoice($customer, [
                                'period_start' => now()->startOfMonth()->toDateString(),
                                'period_end' => $newExpiry,
                                'due_date' => now()->toDateString(),
                                'items' => [
                                    [
                                        'item_type' => 'package',
                                        'description' => "প্যাকেজ রিনিউ বিল: " . ($package?->name ?? 'ইন্টারনেট বিল') . " ({$validityDays} দিন)",
                                        'unit_price' => $amount,
                                        'quantity' => 1,
                                    ]
                                ],
                                'notes' => 'মুভ টু চলতি বিল পরিশোধিত',
                            ], $userId);

                            app(BillingService::class)->collectPayment($customer, [
                                'amount' => $amount,
                                'payment_method' => $paymentMethod,
                                'paid_at' => now()->addSecond(),
                                'invoice_ids' => [$invoice->id],
                                'notes' => 'মুভ টু চলতি বিল পরিশোধিত - আদায়কৃত বিল',
                            ], $userId);
                        }
                    }

                    // Settle any remaining due invoices so customer strictly qualifies for paid_this_month
                    $remainingDueInvoices = $customer->invoices()->where('due_amount', '>', 0)->get();
                    foreach ($remainingDueInvoices as $dueInv) {
                        $customer->decrement('balance', $dueInv->due_amount);
                        $dueInv->update([
                            'paid_amount' => $dueInv->total,
                            'due_amount' => 0,
                            'status' => 'paid',
                        ]);
                    }
                    if ($customer->fresh()->balance > 0) {
                        $customer->update(['balance' => 0]);
                    }
                    break;

                case 'due':
                    // Reverse any completed payments made this month
                    $completedPayments = $customer->payments()
                        ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                        ->where('status', 'completed')
                        ->get();

                    foreach ($completedPayments as $payment) {
                        app(BillingService::class)->reversePayment(
                            $payment,
                            $options['notes'] ?? 'গ্রাহক মুভ: বকেয়া তালিকায় স্থানান্তরের জন্য পেমেন্ট রিভার্স',
                            $userId
                        );
                    }

                    // Ensure customer has due amount / invoice
                    $hasDue = $customer->invoices()->where('due_amount', '>', 0)->exists() || $customer->balance > 0;
                    if (!$hasDue) {
                        $amount = (float) ($options['amount'] ?? ($package?->currentPrice?->price ?? 500));
                        app(BillingService::class)->createInvoice($customer, [
                            'period_start' => now()->startOfMonth()->toDateString(),
                            'period_end' => now()->endOfMonth()->toDateString(),
                            'due_date' => now()->toDateString(),
                            'items' => [
                                [
                                    'item_type' => 'package',
                                    'description' => "মাসিক বিল বকেয়া: " . ($package?->name ?? 'ইন্টারনেট বিল'),
                                    'unit_price' => $amount,
                                    'quantity' => 1,
                                ]
                            ],
                            'notes' => 'মুভ টু বকেয়া তালিকা',
                        ], $userId);
                    }
                    break;

                case 'expiring_3d':
                    $days = (int) ($options['validity_days'] ?? 2);
                    if ($days < 1 || $days > 3) {
                        $days = 2;
                    }
                    $newExpiry = $today->copy()->addDays($days)->toDateString();
                    if ($connections->isNotEmpty()) {
                        foreach ($connections as $conn) {
                            $conn->update([
                                'expiry_date' => $newExpiry,
                                'status' => 'active',
                            ]);
                        }
                    }
                    $customer->update(['status' => 'active']);
                    break;

                case 'expired':
                    $yesterday = $today->copy()->subDay()->toDateString();
                    if ($connections->isNotEmpty()) {
                        foreach ($connections as $conn) {
                            $conn->update([
                                'expiry_date' => $yesterday,
                                'status' => 'expired',
                            ]);
                        }
                    }
                    $customer->update(['status' => 'expired']);
                    break;

                default:
                    throw new Exception("অজানা ক্যাটাগরি: {$targetCategory}");
            }

            AuditLog::log('customer_category_moved', 'customers', $customer, null, [
                'target_category' => $targetCategory,
                'options' => $options,
                'admin_id' => $userId,
            ]);

            return [
                'customer_id' => $customer->id,
                'target_category' => $targetCategory,
                'status' => 'success',
            ];
        });
    }

    /**
     * Bulk move customers to a target category.
     */
    public function bulkMoveCustomerCategories(array $customerIds, string $targetCategory, array $options, int $userId): array
    {
        $customers = Customer::whereIn('id', $customerIds)->get();
        $results = [];
        $errors = [];

        foreach ($customers as $customer) {
            try {
                $results[] = $this->moveCustomerCategory($customer, $targetCategory, $options, $userId);
            } catch (\Throwable $e) {
                $errors[] = "গ্রাহক #{$customer->customer_code}: " . $e->getMessage();
            }
        }

        return [
            'total' => count($customers),
            'success_count' => count($results),
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }
}

