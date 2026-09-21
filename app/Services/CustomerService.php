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
                        $sub->where('balance', '<', 0)
                            ->orWhereHas('invoices', fn($inv) => $inv->where('due_amount', '>', 0));
                    });
                } elseif ($advancedFilter === 'zero_charge_renewed') {
                    // Customers who took advance grace validity (is_zero_charge = 1) without full paid renewal since
                    $q->whereHas('renewals', function ($r) {
                        $r->where('is_zero_charge', true)
                          ->whereRaw('renewals.renewed_at >= COALESCE((SELECT MAX(p.paid_at) FROM payments p WHERE p.customer_id = renewals.customer_id), "1970-01-01")');
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
            ->paginate($perPage);
    }

    /**
     * Get real-time counts for advanced customer categories.
     */
    public function getCustomerFilterCounts(?int $areaId = null): array
    {
        $today = now()->toDateString();
        $in3Days = now()->addDays(3)->toDateString();

        $base = Customer::query()->when($areaId, fn($q) => $q->where('area_id', $areaId));

        return [
            'all' => (clone $base)->count(),
            'due' => (clone $base)->where(function ($sub) {
                $sub->where('balance', '<', 0)
                    ->orWhereHas('invoices', fn($inv) => $inv->where('due_amount', '>', 0));
            })->count(),
            'expiring_3d' => (clone $base)->whereHas('connections', fn($c) => $c->whereBetween('expiry_date', [$today, $in3Days]))->count(),
            'expired' => (clone $base)->whereHas('connections', fn($c) => $c->whereNotNull('expiry_date')->where('expiry_date', '<', $today))->count(),
            'zero_charge_renewed' => (clone $base)->whereHas('renewals', function ($r) {
                $r->where('is_zero_charge', true)
                  ->whereRaw('renewals.renewed_at >= COALESCE((SELECT MAX(p.paid_at) FROM payments p WHERE p.customer_id = renewals.customer_id), "1970-01-01")');
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

            // Create Installation Address
            CustomerAddress::create([
                'customer_id' => $customer->id,
                'address_type' => 'installation',
                'full_address' => $data['address'],
                'is_default' => true,
            ]);

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

            // If billing day changed, also align active connection's expiry date
            if ($billingDayChanged) {
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

            // Mark previous active package history as upgraded/superseded
            CustomerPackage::where('customer_id', $customer->id)
                ->where('connection_id', $connection->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'upgraded',
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
            ]);

            return $history;
        });
    }
}
