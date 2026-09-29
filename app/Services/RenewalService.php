<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Renewal;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class RenewalService
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Authoritative Expiry Calculation:
     * - Active / Early renewal: Appends days to current expiry_date.
     * - Expired customer renewal: Starts from today + validity days.
     * - Deduction / Zero-charge shift (Special user rule): Deducts or shifts specified days safely.
     * - Auto Grace Deduction: When paying full renewal, deducts any previously availed unpaid grace days (e.g. 30 - 2 = 28 days).
     */
    public function calculateNewExpiry(Connection $connection, int $days, string $mode = 'standard', bool $isZeroCharge = false): array
    {
        $today = Carbon::today();
        $prevExpiry = $connection->expiry_date ? Carbon::parse($connection->expiry_date) : null;
        $adjustedDays = $days;
        $graceDaysDeducted = 0;

        if ($mode === 'deduct_shift') {
            // User special rule: Reduces or shifts duration directly without charging extra fees
            $base = ($prevExpiry && $prevExpiry->greaterThanOrEqualTo($today)) ? $prevExpiry : $today;
            $newExpiry = $base->copy()->subDays($days);
            return [
                'previous_expiry' => $prevExpiry?->toDateString(),
                'new_expiry' => $newExpiry->toDateString(),
                'validity_days' => $days,
                'grace_deducted' => 0,
                'type' => 'validity_shift',
            ];
        }

        // If paying full package renewal, check if customer availed unpaid advance grace extension earlier
        if (!$isZeroCharge && $connection->customer_id) {
            $lastPaymentDate = Payment::where('customer_id', $connection->customer_id)->max('paid_at') ?? '1970-01-01';
            
            // Total zero charge grace days taken since last payment
            $graceDaysTaken = (int) Renewal::where('customer_id', $connection->customer_id)
                ->where('is_zero_charge', true)
                ->where('renewed_at', '>=', $lastPaymentDate)
                ->sum('validity_days');

            if ($graceDaysTaken > 0) {
                $graceDaysDeducted = min($graceDaysTaken, $days - 1); // Keep at least 1 day
                $adjustedDays = max(1, $days - $graceDaysDeducted);
            }
        }

        if ($prevExpiry && $prevExpiry->greaterThanOrEqualTo($today)) {
            // Early renewal: extend from future expiry date
            $newExpiry = $prevExpiry->copy()->addDays($adjustedDays);
            $type = 'early';
        } else {
            // Expired or new connection: extend from today
            $newExpiry = $today->copy()->addDays($adjustedDays);
            $type = $prevExpiry ? 'expired' : 'standard';
        }

        return [
            'previous_expiry' => $prevExpiry?->toDateString(),
            'new_expiry' => $newExpiry->toDateString(),
            'validity_days' => $adjustedDays,
            'grace_deducted' => $graceDaysDeducted,
            'type' => $type,
        ];
    }

    /**
     * Process Renewal Transaction with atomic safety, invoice/payment creation, and idempotency.
     */
    public function processRenewal(Customer $customer, Connection $connection, array $data, int $userId): Renewal
    {
        // 1. Idempotency Check
        if (!empty($data['idempotency_key'])) {
            $existing = Renewal::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($customer, $connection, $data, $userId) {
            $package = !empty($data['package_id']) 
                ? Package::with('currentPrice')->findOrFail($data['package_id'])
                : $connection->currentPackage()->with('currentPrice')->firstOrFail();

            $validityDays = (int) ($data['validity_days'] ?? ($package->currentPrice?->validity_days ?? 30));
            $isZeroCharge = (bool) ($data['is_zero_charge'] ?? false);
            $mode = $data['mode'] ?? 'standard';

            // 2. Authoritative Expiry Calculation
            $expiryData = $this->calculateNewExpiry($connection, $validityDays, $mode, $isZeroCharge);

            $lastRenewal = Renewal::lockForUpdate()->latest('id')->first();
            $nextNumber = $lastRenewal ? ($lastRenewal->id + 1) : 1;
            $renewalNumber = 'REN-' . date('Y') . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

            $amount = $isZeroCharge ? 0.00 : (float) ($data['amount'] ?? ($package->currentPrice?->price ?? 0.00));
            $invoice = null;
            $payment = null;

            // 3. If standard paid renewal: Generate Invoice and process Payment
            if (!$isZeroCharge && $amount > 0) {
                $invoice = $this->billingService->createInvoice($customer, [
                    'billing_cycle_id' => $data['billing_cycle_id'] ?? null,
                    'period_start' => now()->toDateString(),
                    'period_end' => $expiryData['new_expiry'],
                    'due_date' => now()->toDateString(),
                    'items' => [
                        [
                            'item_type' => 'package',
                            'description' => "Renewal: {$package->name} ({$validityDays} Days)",
                            'unit_price' => $amount,
                            'quantity' => 1,
                        ]
                    ],
                    'notes' => "Automatic renewal invoice for {$connection->connection_code}",
                ], $userId);

                // Auto-collect payment if auto_pay flag is enabled
                if (!empty($data['collect_payment'])) {
                    $payment = $this->billingService->collectPayment($customer, [
                        'amount' => $amount,
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'account_id' => $data['account_id'] ?? null,
                        'reference' => $data['reference'] ?? null,
                        'invoice_ids' => [$invoice->id],
                        'notes' => "Paid renewal for {$package->name}",
                    ], $userId);
                }
            }

            // 4. Update Connection Expiry & Package
            $connection->update([
                'current_package_id' => $package->id,
                'expiry_date' => $expiryData['new_expiry'],
                'status' => 'active',
            ]);

            // Update customer status to active
            $customer->update(['status' => 'active']);

            // 5. Create Immutable Renewal Record
            $renewal = Renewal::create([
                'renewal_number' => $renewalNumber,
                'customer_id' => $customer->id,
                'connection_id' => $connection->id,
                'package_id' => $package->id,
                'invoice_id' => $invoice?->id,
                'payment_id' => $payment?->id,
                'previous_expiry' => $expiryData['previous_expiry'],
                'new_expiry' => $expiryData['new_expiry'],
                'validity_days' => $validityDays,
                'amount' => $amount,
                'is_zero_charge' => $isZeroCharge,
                'renewal_type' => $expiryData['type'],
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'renewed_by' => $userId,
                'renewed_at' => now(),
                'notes' => $data['notes'] ?? ($isZeroCharge ? 'Zero charge validity adjustment' : 'Standard renewal'),
            ]);

            AuditLog::log('connection_renewed', 'billing', $renewal, null, [
                'previous_expiry' => $expiryData['previous_expiry'],
                'new_expiry' => $expiryData['new_expiry'],
                'is_zero_charge' => $isZeroCharge,
                'amount' => $amount,
            ]);

            // Automated Package Renewal Confirmation SMS (Master Plan Milestone 12)
            \App\Jobs\SendCustomerSmsJob::dispatch(
                'renewal_completed',
                $customer->id,
                [
                    'package' => $package->name,
                    'expiry_date' => $expiryData['new_expiry'],
                ],
                $userId,
                \App\Models\Renewal::class,
                $renewal->id
            );

            return $renewal;
        });
    }
}
